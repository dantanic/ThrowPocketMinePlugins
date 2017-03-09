<?php

namespace DyeShot;

use pocketmine\item\Item;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Float;
use pocketmine\entity\Entity;

class DyeShot extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onLoad(){
		Entity::registerEntity(DyeBullet::class);
	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		if($event->getFace() == 255){
			$player = $event->getPlayer();
			$aimPos = $event->getTouchVector();
			$item = $event->getItem();
 			if($item->getId() === 351){
				$event->setCancelled();
				$dyeShot = new DyeBullet(
					$player->getLevel()->getChunk($player->x >> 4, $player->z >> 4, true),
					new Compound("", [
						"Pos" => new Enum("Pos", [
							new Double("", $player->x),
							new Double("", $player->y + $player->getEyeHeight()),
							new Double("", $player->z)
						]),
						"Motion" => new Enum("Motion", [
							new Double("", $aimPos->x),
							new Double("", $aimPos->y),
							new Double("", $aimPos->z)
						]),
						"Rotation" => new Enum("Rotation", [
							new Float("", 0),
							new Float("", 0)
						])
					]),
					$player,
					$item
				);
				$dyeShot->spawnToAll();
			}
 		}
	}
}

class DyeBullet extends \pocketmine\entity\SnowBall{
	protected static $items = [
		0 => [351, 0],
		1 => [351, 1],
		2 => [351, 2],
		3 => [351, 3],
		4 => [351, 4],
		5 => [351, 5],
		6 => [351, 6],
		7 => [351, 7],
		8 => [351, 8],
		9 => [351, 9],
		10 => [351, 10],
		11 => [351, 11],
		12 => [351, 12],
		13 => [351, 13],
		14 => [351, 14],
		15 => [351, 15]
	];
	protected static $particles = [
		0 => [\pocketmine\level\particle\InkParticle::class, []],
		1 => [\pocketmine\level\particle\LavaParticle::class, []],
		2 => [\pocketmine\level\particle\FlameParticle::class, []],
		3 => [\pocketmine\level\particle\FlameParticle::class, []],
		4 => [\pocketmine\level\particle\WaterParticle::class, []],
		5 => [\pocketmine\level\particle\PortalParticle::class, []],
		6 => [\pocketmine\level\particle\WaterDripParticle::class, []],
		7 => [\pocketmine\level\particle\ExplodeParticle::class, []],
		8 => [\pocketmine\level\particle\FlameParticle::class, []],
		9 => [\pocketmine\level\particle\HeartParticle::class, []],
		10 => [\pocketmine\level\particle\FlameParticle::class, []],
		11 => [\pocketmine\level\particle\FlameParticle::class, []],
		12 => [\pocketmine\level\particle\SplashParticle::class, []],
		13 => [\pocketmine\level\particle\FlameParticle::class, []],
		14 => [\pocketmine\level\particle\CriticalParticle::class, []],
		15 => [\pocketmine\level\particle\SmokeParticle::class, []]
	];

	protected $gravity = 0.02;
 	protected $age = 1100;
	protected $item = null;

	public function __construct(\pocketmine\level\format\FullChunk $chunk, Compound $nbt, Entity $shootingEntity = null, Item $item){
		$this->itemDamage = $item->getDamage();
		parent::__construct($chunk, $nbt, $shootingEntity);
	}

	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}
		$motion = $this->getMotion();
		$vec = $this->add(0,0.3,0);
		for($i=0;$i<5;$i++){
			$this->level->addParticle(new self::$particles[$this->itemDamage][0]($vec = $vec->add($motion->x*0.2, $motion->y*0.2, $motion->z*0.2), ...self::$particles[$this->itemDamage][1]), $this->hasSpawned);
		}
		return parent::onUpdate($currentTick);
	}

	public function spawnTo(\pocketmine\Player $player){
		$pk = new \pocketmine\network\protocol\AddItemEntityPacket();
		$pk->eid = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->item = Item::get(...self::$items[$this->itemDamage]);
		$player->dataPacket($pk);
		Entity::spawnTo($player);
  }
}