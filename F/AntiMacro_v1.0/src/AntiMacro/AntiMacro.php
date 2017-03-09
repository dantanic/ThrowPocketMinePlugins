<?php

namespace AntiMacro;

use pocketmine\entity\Effect;
use pocketmine\utils\TextFormat as Color;

class AntiMacro extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	const LOCATION_LAST = 0;
	const LOCATION_COUNT = 1;
	const ROTATION_LAST = 2;
	const ROTATION_COUNT = 3;
	const ANIMATE_LAST = 4;
	const ANIMATE_COUNT = 5;
	const MESSAGE = Color::DARK_RED . "[Warning] 매크로 ";

	private $players = [];

	public function onLoad(){
		date_default_timezone_set("Asia/Seoul");
 		$this->property = (new \ReflectionClass("\\pocketmine\\entity\\Entity"))->getProperty("effects");
		$this->property->setAccessible(true);		
	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}


	public function onPlayerAnimation(\pocketmine\event\player\PlayerAnimationEvent $event){
		$player = $event->getPlayer();
		$location = $player->x . ":" . $player->y . ":" . $player->z;
		$rotation = $player->yaw . ":" . $player->pitch;
		if(!isset($this->players[$name = $player->getName()])){
			$this->players[$name] = [
				self::LOCATION_LAST => $location,
				self::LOCATION_COUNT => 0,
				self::ROTATION_LAST => $rotation,
				self::ROTATION_COUNT => 0,
				self::ANIMATE_LAST => 0,
				self::ANIMATE_COUNT => 0
			];
		}elseif($event->getAnimationType() == 1 && $this->players[$name][self::ANIMATE_LAST] !== 0){
			if(time() - $this->players[$name][self::ANIMATE_LAST] <= 5){
				$effect = Effect::getEffect(Effect::FATIGUE);
				if($player->hasEffect(Effect::FATIGUE)){
					$oldEffect = $player->getEffect(Effect::FATIGUE);
					if($effect->getAmplifier() > $oldEffect->getAmplifier() || $effect->getDuration() < $oldEffect->getDuration()){
						$oldEffect->setAmplifier(254)->setDuration(100)->add($player, true);
						$effects = $this->property->getValue($player);
						$effects[Effect::FATIGUE] = $oldEffect;
						$this->property->setValue($player, $effects);
					}
				}else{
					$player->addEffect($effect->setAmplifier(254)->setDuration(100));		
				}
				$this->players[$name][self::ANIMATE_LAST] = time();
				$this->players[$name][self::ANIMATE_COUNT]++;
				if($this->players[$name][self::ANIMATE_COUNT] >= 50){
					$log = Color::toANSI("[" . date("Y-m-d H:i:s") . "] " . $player->getName());
					@mkdir($folder = $this->getDataFolder());
					if(!file_exists($path = $folder . "MacroLog.txt")){	
						file_put_contents($path, $log);
					}else{
						fwrite($logFile = fopen($path, "a+b"), "\r\n" . $log);
						fclose($logFile);
					}
 					$player->close(self::MESSAGE . "확실" . " : " . $player->getName() . " '신고바랍니다'", self::MESSAGE . "확실");
 				}
 			}else{
				$this->players[$name][self::ANIMATE_LAST] = 0;
				$this->players[$name][self::ANIMATE_COUNT] = 0;
			}
		}
	}

	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
		$player = $event->getPlayer();
		$location = $player->x . ":" . $player->y . ":" . $player->z;
		$rotation = $player->yaw . ":" . $player->pitch;
		if(!isset($this->players[$name = $player->getName()])){
			$this->players[$name] = [
				self::LOCATION_LAST => $location,
				self::LOCATION_COUNT => 0,
				self::ROTATION_LAST => $rotation,
				self::ROTATION_COUNT => 0,
				self::ANIMATE_LAST => 0,
				self::ANIMATE_COUNT => 0
			];
		}elseif($event->getBlock()->getID() == 1 && ($this->players[$name][self::ANIMATE_LAST] === 0 || time() - $this->players[$name][self::ANIMATE_LAST] > 5)){
			$this->players[$name][self::LOCATION_COUNT]++; 
			if($this->players[$name][self::LOCATION_LAST] != $location){
				$this->players[$name][self::LOCATION_COUNT] = 0; 
			}elseif($this->players[$name][self::LOCATION_COUNT] >= 30){
				$player->sendPopup(self::MESSAGE . "의심");
				if($this->players[$name][self::LOCATION_COUNT] == 50){
					$this->players[$name][self::ANIMATE_LAST] = time();
					$this->getServer()->broadcastMessage(self::MESSAGE . "의심" . " : " . $player->getName());
					return;
				}
			}
			$this->players[$name][self::LOCATION_LAST] = $location;
			$this->players[$name][self::ROTATION_COUNT]++; 
			if($this->players[$name][self::ROTATION_LAST] != $rotation){
				$this->players[$name][self::ROTATION_COUNT] = 0; 
			}elseif($this->players[$name][self::ROTATION_COUNT] >= 10){
				$player->sendPopup(self::MESSAGE . "의심");
				if($this->players[$name][self::ROTATION_COUNT] == 15){
					$this->players[$name][self::ANIMATE_LAST] = time();
					$this->getServer()->broadcastMessage(self::MESSAGE . "의심" . " : " . $player->getName());
					return;
				}
			}
			$this->players[$name][self::ROTATION_LAST] = $rotation;
		}
	}
}