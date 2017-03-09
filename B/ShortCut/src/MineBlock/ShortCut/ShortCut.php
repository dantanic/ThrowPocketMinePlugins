<?php

namespace MineBlock\ShortCut;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
 
class ShortCut extends PluginBase implements Listener{
	public function onEnable(){
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
 	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$sc = $this->sc;
		$rm = TextFormat::RED . "Usage: /ShortCut ";
		$mm = "[ShortCut] ";
		$ik = $this->isKorean();
		switch(strtolower(array_shift($sub))){
			case "add":
			case "a":
			case "추가":
				if(!isset($sub[0]) || !isset($sub[1])){
					$r = $rm . "Add(A) " . ($ik ? "<단축명> <명령어>" : "<Alias> <Command>");
				}else{
					$commandAlias = strtolower(array_shift($sub));
					$command = str_replace([".@", "_@", "-@"], ["@", "@", "@"], implode(" ", $sub));
					$sc[$commandAlias] = $command;
					$r = $mm . ($ik ? " 추가됨" : " add") . "[$commandAlias] => $command";
					;
				}
			break;
			case "del":
			case "d":
			case "삭제":
			case "제거":
				if(!isset($sub[0])){
					$r = $rm . "Del(D) " . ($ik ? "<단축명>" : "<Alias>");
				}else{
					$a = strtolower($sub[0]);
					if(!isset($sc[$a])){
						$r = "$mm [$a] " . ($ik ? " 목록에 존재하지 않습니다..\n   $rm 목록 " : " does not exist.\n   $rm List(L)");
					}else{
						$commandAlias = $sc[$a];
						unset($sc[$a]);
						$r = $mm . ($ik ? " 제거됨" : " del") . "[$a] =>$a";
					}
				}
			break;
			case "reset":
			case "r":
			case "리셋":
			case "초기화":
				$sc = [];
				$r = $mm . ($ik ? " 리셋됨." : " Reset");
			break;
			case "list":
			case "l":
			case "목록":
			case "리스트":
				$page = 1;
				if(isset($sub[1]) && is_numeric($sub[1])) $page = max(floor($sub[1]), 1);
				$list = array_chunk($sc, 5, true);
				if($page >= ($c = count($list))) $page = $c;
				$r = $mm . ($ik ? "이지커맨드 목록 (페이지" : "ShortCut List (Page") . " $page/$c) \n";
				$num = ($page - 1) * 5;
				foreach($list[$page - 1] as $k => $v){
					$num++;
					$r .= "  [$num] $k : [$v]\n";
				}
			break;
			default:
				return false;
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if($this->sc !== $sc){
			$this->sc = $sc;
			$this->saveYml();
		}
		return true;
	}

	/**
	 * @priority LOWEST
	 */
	public function onServerCommand(ServerCommandEvent $event){
		$event->setCommand($this->commandAlias($event->getCommand()));
		if(($m = $this->CommandShortcut($event)) && $m !== true) $event->setCommand($m);
		elseif($m === false) $event->setCancelled();
	}

	/**
	 * @priority LOWEST
	 */
	public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event){
		if(strpos($cmd = $event->getMessage(), "/") === 0){
		if($isCommand) $event->setMessage("/".$this->commandAlias(substr($cmd, 1)));
		if(($m = $this->CommandShortcut($event, $isCommand)) && $m !== true) $event->setMessage(($isCommand ? "/" : "") . $m);
		elseif($m === false) $event->setCancelled();
	}

	public function commandAlias($cmd){
		$sc = $this->sc;
		$arr = explode(" ", $cmd);
		while((isset($sc[strtolower($arr[0])]))){
			$arr[0] = $sc[strtolower($arr[0])];
			$cmd = implode(" ", $arr);
			$arr = explode(" ", $cmd);
		}
		return $cmd;
	}

	public function CommandShortcut($event, $isCommand = true){
		if($event->isCancelled()) return false;
		if($event instanceof PlayerCommandPreprocessEvent){
			$cmd = $event instanceof PlayerChatEvent ? $event->getMessage() : $isCommand ? substr($event->getMessage(), 1) : $event->getMessage();
			$sender = $event->getPlayer();
			$ip = true;
		}else{
			$cmd = $event->getCommand();
			$sender = $event->getSender();
			$ip = false;
		}
		if(!$sender->hasPermission("eazycommand.use")) return true;
		$arr = explode(" ", $cmd);
		$scl = [];
		$ps = $this->getServer()->getOnlinePlayers();
		foreach($arr as $k => $v){
			if(strpos($v, "@") === 0){
				switch(substr($v, 1)){
					case "player":
					case "p":
						$arr[$k] = $sender->getName();
					break;
					case "x":
						if($ip) $arr[$k] = $sender->x;
					break;
					case "y":
						if($ip) $arr[$k] = $sender->y;
					break;
					case "z":
						if($ip) $arr[$k] = $sender->z;
					break;
					case "world":
					case "w":
						if($ip) $arr[$k] = $sender->getLevel()->getName();
					break;
					case "all":
					case "a":
						if($sender->isOp() && count($ps) > 0) $scl[] = $k;
					break;
					case "random":
					case "r":
						$arr[$k] = count($ps) < 1 ? "" : $ps[array_rand($ps)]->getName();
					break;
					case "server":
					case "s":
						$arr[$k] = $this->getServer()->getServerName();
					break;
					case "version":
					case "v":
						$arr[$k] = $this->getServer()->getApiVersion();
					break;
					case "mineblock":
					case "d":
						$arr[$k] = ["마인블럭", "MineBlock", "마인블럭플러그인", "MineBlock"][rand(0, 3)];
					break;
				}
			}
		}
		foreach($arr as $k => $v)
			$arr[$k] = str_replace([".@", "_@", "-@"], ["@", "@", "@"], $v);
		if(count($scl) !== 0){
			$event->setCancelled();
			foreach($ps as $p){
				foreach($scl as $v)
					$arr[$v] = $p->getName();
				$cmd = implode(" ", $arr);
				$ep = false;
				if($event instanceof PlayerCommandPreprocessEvent){
					$ev = new PlayerCommandPreprocessEvent($sender, "/" . $cmd);
					$ep = true;
				}elseif(!$isCommand){
					$this->getServer()->getPluginManager()->callEvent($ev = new PlayerChatEvent($sender, $cmd));
					if(!$ev->isCancelled()) $this->getServer()->broadcastMessage(sprintf($ev->getFormat(), $ev->getPlayer()->getDisplayName(), $ev->getMessage()), $ev->getRscipients());
					return false;
				}else{
					$ev = new ServerCommandEvent($sender, $cmd);
				}
				$this->getServer()->getPluginManager()->callEvent($ev);
				if(!$ev->isCancelled()) $this->getServer()->dispatchCommand($sender, $ep ? substr($ev->getMessage(), 1) : $ev->getCommand());
			}
			return false;
		}else{
			return implode(" ", $arr);
		}
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->sc = (new Config($this->getDataFolder() . "ShortCut.yml", Config::YAML))->getAll();
	}

	public function saveYml(){
		ksort($this->sc);
		$sc = new Config($this->getDataFolder() . "ShortCut.yml", Config::YAML);
		$sc->setAll($this->sc);
		$sc->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}