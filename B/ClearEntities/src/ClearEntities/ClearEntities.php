<?php

namespace MineBlock\ClearEntities;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Arrow;
use pocketmine\entity\Item;
use pocketmine\entity\Living;
use pocketmine\event\Listener;
use 
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;

class ClearEntities extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	const ALL = 0;
	const ITEM = 1;
	const ARROW = 2:
	const MOB = 3;

	public function onEnable(){
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[1])) return false;
		$ik = $this->isKorean();
		$ce = $this->ce;
		switch(strtolower($sub[0])){
			case "item":
			case "i":
			case "아이템":
				$mode = self::ITEM;
			break;
			case "arrow":
			case "a":
			case "화살":
				$mode = self::ARROW;
			break;
			case "monster":
			case "mob":
			case "m":
			case "몬스터":
				$mode = self::MOB;
			break;
			default:
				$mode = self::ALL;
			break;
		}
		$entities = [];
		foreach($this->getServer()->getLevels() as $level){
			if(isset($sub[2] && strtolower($sub[2]) !== strtolower($level->getFolderName()) continue;
			foreach($level->getEntities() as $entity){
				switch($mode){
					case self::ITEM:
						if($entity instanceof Item) $entities[] = $entity;
					break;
					case self::ARROW:
						if($entity instanceof Arrow) $entities[] = $e;
					break;
					case self::MOB:
						if(!$entity instanceof Item && !$entity instanceof Arrow && !$entity instanceof Player) $entities[] = $entity;
					break;
					default:
						if(!$entity instanceof Player) $entities[] = $entity;
					break;
				}
			}
		}
		$count = count($entities);
		switch(strtolower($sub[1])){
			case "view":
			case "v":
			case "보기":
				$name = !isset($sub[2]) ? ($ik ? "이 서버" : "This server") : "\"" . $w->getFolderName() . "\" " . ($ik ? "월드에" : "world");
				$r = Color::YELLOW . "[ClearEntities] " . ($ik ? $name . "에는 " . $count . "개의 엔티티가 있습니다." : "$name has $count Entities.");
			break;
			case "clear":
			case "c":
			case "클리어":
			case "초기화":
				foreach($entities as $entity) $entity->close();
				$r = Color::YELLOW . "[ClearEntities] " . ($ik ? "엔티티를 " . $count . "마리 제거했습니다." : " Clear Entities ($count)");
			break;
			case "set":
			case "s":
			case "설정":
				if(!isset($sub[2])){
					$r = Color::RED . "[ClearEntities] Usage: /ClearEntities Set(S) " . ($ik ? "<아이템|화살> <초>" : "<Item|Arrow> <Sec>");
				}else{
					if($mode == self::ITEM){
						$ce["Item"] = $sub[2] = floor($sub[2]);
						$r = Color::YELLOW . "[ClearEntities] " . ($ik ? "아이템 엔티티의 생존시간을 $sub[2] 초로 설정했습니다." : "Set Item Entity's lifespan to $sub[2] sec");
					}elseif($mode == self::ARROW){
						$ce["Arrow"] = $sub[2] = floor($sub[2]);
						$r = Color::YELLOW . "[ClearEntities] " . ($ik ? "화살 엔티티의 생존시간을 $sub[2] 초로 설정했습니다." : "Set Arrow Entity's lifespan to $sub[2] sec");
					}else{
						$r = Color::RED . "[ClearEntities] Usage: /ClearEntities Set(S) " . ($ik ? "<아이템|화살> <초>" : "<Item|Arrow> <Sec>");
					}
				}
			break;
			default:
				return false;
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if($this->ce !== $ce){
			$this->ce = $ce;
			$this->saveYml();
		}
		return true;
	}

	public function onEntitySpawn(\pocketmine\event\entity\EntitySpawnEvent $event){
		if(($isArrow = ($entity = $event->getEntity()) instanceof Arrow) || $entity instanceof Item){
 			$property = (new \ReflectionClass("\\pocketmine\\entity\\".($type = ($isArrow ? "Arrow" : "Item"))))->getProperty("age");
			$property->setAccessible(true);
			$property->setValue($entity, ($isArrow ? 1200 : 6000) - $this->ce[$type] * 20);
		}
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->ce = (new Config($this->getDataFolder() . "ClearEntities.yml", Config::YAML, ["Item" => 300, "Arrow" => 60]))->getAll();
	}

	public function saveYml(){
		$ce = new Config($this->getDataFolder() . "ClearEntities.yml", Config::YAML);
		$ce->setAll($this->ce);
		$ce->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}