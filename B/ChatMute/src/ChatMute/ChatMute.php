<?php

namespace ChatMute;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;

class ChatMute extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onEnable(){
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$ik = $this->isKorean();
		$cm = $this->cm;
		switch(strtolower($sub[0])){
			case "cm":
			case "m":
			case "추가":
			case "차단":
			case "음소거":
				if(!isset($sub[1])){
					$sender->sendMessage(Color::RED . "Usage: /ChatMute Mute(M) " . ($ik ? "<플레이어명>" : "<PlayerName>"));
					return true;
				}else{
					if(!$player = $this->getServer()->getPlayer($sub[1])){
						$r = Color::RED . "[ChatMute] " . $sub[1] . ($ik ? " 는 잘못된 플레이어명입니다." : "is invalid player");
					}else{
						$cms = $cm["Player"];
						if(isset($cms[$name = strtolower($player->getName())])){
							unset($cms[$name]);
							$r = Color::YELLOW . "[ChatMute] " . $n . ($ik ? "의 음소거를 해제합니다." : " has UnMute");
						}else{
							$cms[$name] = true;
							$r = Color::YELLOW . "[ChatMute] " . $n . ($ik ? "의 음소거를 설정합니다." : " has Mute");
						}
						$cm["Player"] = $cms;
					}
				}
			break;
			case "allcm":
			case "a":
			case "전체추가":
			case "전체차단":
			case "전체음소거":
				if($cm["All"]){
					$cm["All"] = false;
					$m = Color::YELLOW . "[ChatMute] " . ($ik ? "모든 채팅 음소거를 해제합니다." : "AllMute Off");
				}else{
					$cm["All"] = true;
					$m = Color::YELLOW . "[ChatMute] " . ($ik ? "모든 채팅 음소거를 설정합니다." : "AllMute On");
				}
			break;
			default:
				return false;
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		elseif(isset($m)) $this->getServer()->broadcastMessage($m);
		if($this->cm !== $cm){
			$this->cm = $cm;
			$this->saveYml();
		}
		return true;
	}

	public function onPlayerChat(\pocketmine\event\player\PlayerChatEvent $event){
		if(!$event->isCancelled()){
			$player = $event->getPlayer();
			$ik = $this->isKorean();
			if($this->cm["All"] && !$player->hasPermission("chatmute.chat") || isset($this->cm["Player"][strtolower($player->getName())])){
				$player->sendMessage(Color::PURPLE . "[ChatMute]" . ($ik ? "채팅이 차단되었습니다.." : "Chat is blocked"));
				$event->setCancelled();
			}
		}
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->cm = (new Config($this->getDataFolder() . "ChatMute.yml", Config::YAML, ["All" => false, "Player" => []]))->getAll();
	}

	public function saveYml(){
		$cm = new Config($this->getDataFolder() . "ChatMute.yml", Config::YAML);
		$cm->setAll($this->cm);
		$cm->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}