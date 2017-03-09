<?php

namespace MineBlock\CommandLog;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\server\RemoteServerCommandEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class CommandLog extends PluginBase implements Listener{

	public function onEnable(){
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		if(isset($sub[1])) $sub[1] = strtolower($sub[1]);
		$cl = $this->cl;
		$rm = TextFormat::RED . "Usage: /CommandLog ";
		$mm = "[CommandLog] ";
		$ik = $this->isKorean();
		switch(strtolower($sub[0])){
			case "view":
			case "v":
			case "보기":
			case "뷰":
				if(!isset($sub[1])){
					$r = $rm . "View(V)" . ($ik ? "<플레이어명> <페이지>" : "<PlayerName> <Page>");
				}elseif(!isset($cl[$sub[1]])){
					$sender->sendMessage($mm . $sub[1] . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player"));
				}else{
					if(!isset($sub[2]) || !is_numeric($sub[2] || $sub[2] < 1)) $sub[2] = 1;
					$page = round($sub[2]);
					if(isset($sub[0]) && is_numeric($sub[0])) $page = round($sub[0]);
					$list = ceil(count($cl[$sub[1]]) / 5);
					if($page >= $list) $page = $list;
					$r = $mm . $sub[1] . ($ik ? "' 로그 (페이지" : "' Command Log (Page") . " $page/$list) \n";
					$num = 0;
					foreach($cl[$sub[1]] as $k => $v){
						$num++;
						if($num + 5 > $page * 5 && $num <= $page * 5) $r .= "  [$k] $v\n";
					}
				}
			break;
			case "clear":
			case "c":
			case "초기화":
			case "클리어":
				if(!isset($sub[1])){
					$r = $rm . "Clear(C) " . ($ik ? "<플레이어명>" : "<PlayerName>");
				}elseif(!isset($cl[$sub[1]])){
					$sender->sendMessage($mm . $sub[1] . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player"));
				}else{
					$cl[$sub[1]] = [];
					$r = $mm . ($ik ? $sub[1] . "님의 명령어 로그를 제거합니다." : "Clear the $sub[1]'s Command log");
				}
			break;
			case "allclear":
			case "ac":
			case "전체초기화":
			case "전체클리어":
				$cl = [];
				$r = $ik ? "모든 명령어 로그를 제거합니다.'" : "Clear the All Command log";
			break;
			default:
				return false;
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if($this->cl !== $cl){
			$this->cl = $cl;
			$this->saveYml();
		}
		return true;
	}

	public function onServerCommand(ServerCommandEvent $event){
		$this->log($event->getCommand(), $event->getSender());
	}

	public function onRemoteServerCommand(RemoteServerCommandEvent $event){
		$this->log($event->getCommand(), $event->getSender());
	}

	public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event){
		if(strpos($cmd = $event->getMessage(), "/") === 0) $this->log(substr($cmd, 1), $event->getPlayer());
	}

	public function log($cmd, $sender){
		if(($name = strtolower($sender->getName())) !== "console" && strtolower($cmd) !== "list" && strtolower($cmd) !== "stop"){
			$this->cl[$name][date("Y:m:d|H:i:s", time())] = $cmd;
			$this->saveYml();
		}
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->cl = (new Config($this->getDataFolder() . "CommandLog.yml", Config::YAML, []))->getAll();
	}

	public function saveYml(){
		ksort($this->cl);
		$cl = new Config($this->getDataFolder() . "CommandLog.yml", Config::YAML, []);
		$cl->setAll($this->cl);
		$cl->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}
