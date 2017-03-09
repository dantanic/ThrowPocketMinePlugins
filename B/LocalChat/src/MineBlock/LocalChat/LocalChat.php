<?php

namespace MineBlock\LocalChat;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class LocalChat extends PluginBase implements Listener{
	public function onEnable(){
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$mm = "[LocalChat] ";
		$lc = $this->lc;
		$ik = $this->isKorean();
		switch(strtolower($sub[0])){
			case "on":
				if($lc["On"]){
					$r = $mm . ($ik ? "로컬챗이 이미 켜져잇습니다." : "LocalChat is already enable");
				}else{
					$lc["On"] = true;
					$m = $mm . ($ik ? "로컬챗을 켭니다." : "LocalChat is Enable");
				}
			break;
			case "off":
				if(!$lc["On"]){
					$r = $mm . ($ik ? "로컬챗이 이미 꺼져잇습니다." : "LocalChat is already disable");
				}else{
					$lc["On"] = false;
					$m = $mm . ($ik ? "로컬챗을 끕니다." : "LocalChat is Disable");
				}
			break;
			default:
				if(is_numeric($sub[0])){
					$lc["Local"] = $sub[0] > 0 ? $sub[0] : 0;
					$m = $mm . ($ik ? "채팅 거리가 $sub[0] 블럭으로 설정되었습니다." : "Chat range is set $sub[0] block.");
				}else{
					return false;
				}
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if(isset($m)) $this->getServer()->broadCastMessage($m);
		if($this->lc !== $lc){
			$this->lc = $lc;
			$this->saveYml();
		}
		return true;
	}

	public function onPlayerChat(PlayerChatEvent $event){
		$recipients = $event->getRecipients();
		$p = $event->getPlayer();
		foreach($recipients as $k => $v){
			if($v instanceof Player && !$v->hasPermission("localchat.hear")){
				if($p->getLevel()->getName() !== $v->getLevel()->getName() || $p->distance($v) > $this->lc["Local"]) unset($recipients[$k]);
			}
		}
		$event->setRecipients($recipients);
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->lc = (new Config($this->getDataFolder() . "LocalChat.yml", Config::YAML, ["On" => true, "Local" => 100]))->getAll();
	}

	public function saveYml(){
		$lc = new Config($this->getDataFolder() . "LocalChat.yml", Config::YAML, ["On" => true, "Local" => 100]);
		$lc->setAll($this->lc);
		$lc->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}
