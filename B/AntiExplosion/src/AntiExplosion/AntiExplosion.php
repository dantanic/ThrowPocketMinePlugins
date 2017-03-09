<?php

namespace MineBlock\AntiExplosion;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;

class AntiExplosion extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	const OFF = 0;
	const ON = 1;
	const PROTECT = 2;

	public function onEnable(){
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$ik = $this->isKorean();
		$ae = $this->ae;
		switch(strtolower($sub[0])){
			case "on":
			case "1":
			case "온":
			case "켜짐":
			case "활성화":
				$ae["Mode"] = self::ON;
				$r = Color::YELLOW . "[AntiExplosion] " . ($ik ? "폭발을 방지합니다." : "Now provent the explosion");
			break;
			case "off":
			case "0":
			case "오프":
			case "꺼짐":
			case "비활성화":
				$ae["Mode"] = self::OFF;
				$r = Color::YELLOW . "[AntiExplosion] " . ($ik ? "폭발을 방지하지않습니다." : "Now not provent the explosion");
			break;
			case "protect":
			case "p":
			case "2":
			case "보호":
			case "프로텍트":
				$ae["Mode"] = self::PROTECT;
				$r = Color::YELLOW . "[AntiExplosion] " . ($ik ? "폭발의 블럭파괴를 방지합니다." : "Now provent the explosion's block break");
			break;
			default:
				return false;
			break;
		} 
		if(isset($r)) $sender->sendMessage($r);
		elseif(isset($m)) $this->getServer()->broadcastMessage($m);
		if($this->ae !== $ae){
			$this->ae = $ae;
			$this->saveYml();
		}
		return true;
	}

	public function onExplosionPrime(\pocketmine\event\entity\ExplosionPrimeEvent $event){
		switch($this->ae["Mode"]){
			case 1:
				$event->setCancelled();
			break;
			case 2:
				$event->setBlockBreaking(false);
			break;
		}
	}

 	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->ae = (new Config($this->getDataFolder() . "AntiExplosion.yml", Config::YAML, ["Mode" => self::ON]))->getAll();
	}

	public function saveYml(){
		$ae = new Config($this->getDataFolder() . "AntiExplosion.yml", Config::YAML);
		$ae->setAll($this->ae);
		$ae->save();
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "korean";
	}
}