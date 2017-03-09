<?php

namespace Fishing;

use pocketmine\item\Item;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Float;
use pocketmine\entity\Entity;

class Fishing extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onLoad(){
		$this->players = [];
		if(!Item::isCreativeItem($item = new Item(346))) Item::addCreativeItem($item);
		Entity::registerEntity(FishingHook::class);
	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

 	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		if($event->getFace() == 255 && $event->getItem()->getID() === 346){
			$player = $event->getPlayer();
			$aimPos = $event->getTouchVector();
			$event->setCancelled();
			if(isset($this->players[$name = $player->getName()]) && $this->players[$name]->getHealth() !== 0){
				$this->players[$name]->goBack();
				if($this->players[$name]->closed) unset($this->players[$name]);
 			}else{
				$fishingHook = new FishingHook(
					$player->getLevel()->getChunk($player->x >> 4, $player->z >> 4, true),
					new Compound("", [
						"Pos" => new Enum("Pos", [
							new Double("", $player->x),
							new Double("", $player->y + $player->getEyeHeight()),
							new Double("", $player->z)
						]),
						"Motion" => new Enum("Motion", [
							new Double("", $aimPos->x * 0.7),
							new Double("", $aimPos->y * 0.7),
							new Double("", $aimPos->z * 0.7)
						]),
						"Rotation" => new Enum("Rotation", [
							new Float("", 0),
							new Float("", 0)
					])
					]),
					$player
				);
				$fishingHook->spawnToAll();
				$this->players[$name] = $fishingHook;
			}
 		}
	}

	public function onPlayerQuit(\pocketmine\event\player\PlayerQuitEvent $event){
		if(isset($this->players[$name = $event->getPlayer()->getName()])){
			$this->players[$name]->kill();
			unset($this->players[$name]);
 		}
	}

	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
		if($event->getItem()->getID() == 346) $event->setCancelled();
	}
}

class FishingHook extends \pocketmine\entity\Projectile{
	const NETWORK_ID = 77;

	public static $fishProbability = null;

	public $width = 0.2;
	public $length = 0.2;
	public $height = 0.42;
	protected $gravity = 0.03;
	protected $drag = 0.02;
	protected $isGoBack = false;
	protected $fish = null;
	protected $fishEid = null;
	protected $fishTick = 0;

	public function __construct(\pocketmine\level\format\FullChunk $chunk, Compound $nbt, Entity $shootingEntity = null){
		parent::__construct($chunk, $nbt, $shootingEntity);
		if(!$shootingEntity instanceof \pocketmine\Player) $this->kill();
	}
		
 	protected function initEntity(){
		if($this->shootingEntity === null){
			$this->kill();
		}else{
			parent::initEntity();
			if(self::$fishProbability == null){
				self::$fishProbability = [];
				foreach([60, 25, 2, 13] as $key => $persent){
					for($i = 0; $i < $persent; $i++) self::$fishProbability[] = $key;
				}
			}
 		}
	}

	public function saveNBT(){
		$this->kill();
	}

 	public function onUpdate($currentTick){
		if($this->closed) return false;
		if($this->shootingEntity === null){
			$this->kill();
			return false;
		}
		$this->timings->startTiming();
		$hasUpdate = parent::onUpdate($currentTick);
		if($this->isGoBack){
			if($this->fish instanceof Item){
				if($this->fishEid == null){
					$pk = new \pocketmine\network\protocol\AddItemEntityPacket();
					$pk2 = new \pocketmine\network\protocol\SetEntityDataPacket();
					$pk->eid = $this->fishEid = bcadd("1095216660480", mt_rand(0, 0x7fffffff));
					$pk->x = $this->x;
					$pk->y = $this->y - 0.3;
					$pk->z = $this->z;
					$pk->speedX = $pk->speedY = $pk->speedZ = 0;
					$pk->item = $this->fish;
 					foreach($this->hasSpawned as $player){
						$player->dataPacket($pk);
					}
				}
				$pk = new \pocketmine\network\protocol\MovePlayerPacket();
				$pk->eid = $this->fishEid;
				$pk->x = $this->x;
				$pk->y = $this->y - 0.3;
				$pk->z = $this->z;
				$pk->speedX = $pk->speedY = $pk->speedZ = 0;
				foreach($this->hasSpawned as $player) $player->dataPacket($pk);
			}
			if($this->distance($this->shootingEntity->add(0, $this->shootingEntity->getEyeHeight(), 0)) < 2){
				$this->kill();
				$hasUpdate = true;
				if($this->fish instanceof Item){
					$this->shootingEntity->getInventory()->addItem($this->fish);
					$this->getLevel()->addSound(new \pocketmine\level\sound\PopSound($this));
					$this->fish = null;
					$pk = new \pocketmine\network\protocol\RemoveEntityPacket();
					$pk->eid = $this->fishEid;
					foreach($this->hasSpawned as $player) $player->dataPacket($pk);
 				}
			}else{
				$this->motionX = cos($atan2 = atan2($this->shootingEntity->z - $this->z, $this->shootingEntity->x - $this->x)) * 0.7;
				$this->motionY = ($this->shootingEntity->y + $this->shootingEntity->getEyeHeight() - $this->y) * 0.2;
				$this->motionZ = sin($atan2) * 0.7;
			}
		}elseif($this->fish instanceof Item){
			$this->gravity = $this->isInsideOfWater() ? -0.06 : 0.06;
			if(++$this->fishTick > 75){
				$this->fishTick = 0;
				$this->fish = null;
				$this->getLevel()->addParticle(new \pocketmine\level\particle\MobSpawnParticle($this, 1, 1));
				$this->getLevel()->addSound(new \pocketmine\level\sound\FizzSound($this));
			}else{
				$this->getLevel()->addParticle(new \pocketmine\level\particle\SplashParticle($this->add(0, 1, 0)));
			}
 		}elseif($this->isInsideOfWater()){
			$this->gravity = -0.01;
 			$this->motionX = $this->motionY = $this->motionZ = 0;
			if(rand(0,1000) == 0){
				$this->fish = Item::get(349, self::$fishProbability[array_rand(self::$fishProbability)], 1);
			}
 		}else $this->gravity = 0.03;
/*
		$pk = new \pocketmine\network\protocol\PlayerEquipmentPacket();
		$pk->eid = $this->getId();
		$pk->item = $this->fish instanceof Item ? $this->fish->getID() : 0;
		$pk->meta = $this->fish instanceof Item ? $this->fish->getDamage() : 0;
		$pk->slot = 0;
		$pk->selectedSlot = 0;
		$pk->isEncoded = true;
 		$this->shootingEntity->dataPacket($pk);
*/
		$this->timings->stopTiming();
		return $hasUpdate;
	}

	public function goBack(){
		if(!$this->isGoBack) $this->isGoBack = true;
	}

	public function spawnTo(\pocketmine\Player $player){
		if($this->shootingEntity === null) return false;
		$pk = new \pocketmine\network\protocol\AddEntityPacket();
		$pk->type = FishingHook::NETWORK_ID;
		$pk->eid = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$eid = $this->shootingEntity->getId();
		$pk->metadata = $this->dataProperties;
		$pk->metadata[self::DATA_SHOOTER_ID] = [self::DATA_TYPE_LONG, $eid];
		if($this->shootingEntity === $player){
			$pk->metadata[self::DATA_NAMETAG] = [self::DATA_TYPE_STRING, \pocketmine\utils\TextFormat::DARK_BLUE . "•"];
			$pk->metadata[self::DATA_SHOW_NAMETAG] = [self::DATA_TYPE_BYTE, 1];
 		}
 		$player->dataPacket($pk);
 		parent::spawnTo($player);
	}
}