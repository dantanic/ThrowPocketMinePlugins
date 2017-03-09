<?php
namespace MineBlock\HealBed;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\RemovePlayerPacket;
use pocketmine\scheduler\CallbackTask;

class FroatingText{
	public $closed = false;
	private $server, $target, $name, $id, $schedule;
	private $show = false;
	private $x = 0, $y = 0, $z = 0, $healTick = 0;

	public function __construct(Player $target, $name = ""){
		$this->server = Server::getInstance();
		$this->target = $target;
		$this->name = $name;
		$this->AddPlayerPacket = new AddPlayerPacket();
		$this->AddPlayerPacket->eid = $this->AddPlayerPacket->clientID = $this->id = bcadd("1095216660480", mt_rand(0, 0x7fffffff));
		$this->AddPlayerPacket->yaw = $this->AddPlayerPacket->pitch = $this->AddPlayerPacket->item = $this->AddPlayerPacket->meta = $this->AddPlayerPacket->slim = false;
		$this->AddPlayerPacket->skin = str_repeat("\x00", 64 * 32 * 4);
		$this->AddPlayerPacket->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_BYTE, 1 << Entity::DATA_FLAG_INVISIBLE],
			Entity::DATA_SHOW_NAMETAG => [Entity::DATA_TYPE_BYTE, 1],
			Entity::DATA_NO_AI => [Entity::DATA_TYPE_BYTE, 1]
		];
		$this->RemovePlayerPacket = new RemovePlayerPacket();
		$this->RemovePlayerPacket->eid = $this->RemovePlayerPacket->clientID = $this->id;
		$this->server->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this,"onTick"]), 2);
	}

	public function onTick(){
		$t = $this->target;
		if($this->closed){
			$this->despawn();
			if($this->schedule !== null){
				$this->server->getScheduler()->cancelTesk($this->schedule->getTaskId());
				$this->schedule = null;
			}
 		}elseif(!$t->loggedIn){
 			$this->closed = true;
		}else{
			if($this->healTick >= 30){
				$ev = new EntityRegainHealthEvent($t, 1, EntityRegainHealthEvent::CAUSE_MAGIC);
				$t->heal($ev->getAmount(), $ev);
	 			$this->healTick = 0;
			}
			$this->healTick++;
			$name = "Healing...";
			if($this->healTick < 28){
				$name .= "\n    " . (3 - floor($this->healTick * 0.1)) . "...";
				$name .= ["-", "\\", ".|", "/"][floor($this->healTick * 0.5) % 4];
			}
			$this->setName($name);
			$property = (new \ReflectionClass("\\pocketmine\\Player"))->getProperty("sleeping");
			$property->setAccessible(true);
			$b = $t->getLevel()->getBlock($property->getValue($t));
			$xTabel = [1 => 2, 3 => -2, 9 => 2, 11 => -2];
			$x = isset($xTabel[$dmg = $b->getDamage()]) ? $xTabel[$dmg] : 0.5;
			$zTabel = [0 => -2, 2 => 2, 8 => -2, 10 => 2];
			$z = isset($zTabel[$dmg]) ? $zTabel[$dmg] : 0.5;
			$this->x = $b->x - $x;
			$this->y = $t->y;
			$this->z = $b->z - $z;
			$this->spawn();
			$pk = new MovePlayerPacket();
			$pk->eid = $this->id;
			$pk->yaw = $pk->bodyYaw = $pk->pitch = 0;
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
 			$t->dataPacket($pk);
		}
	}

	public function setName($name = ""){
		if($this->name !== $name){
			$this->name = $name;
			$this->despawn();
			$this->spawn();
		}
	}

	public function getName(){
		return $this->name;
	}

	public function spawn(){
		if(!$this->show && $this->target->spawned){
			$pk = clone $this->AddPlayerPacket;
			$pk->username = $this->name;
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$this->target->dataPacket($pk);
			$this->show = true;
		}
	}

	public function despawn(){
		if($this->show){
			$this->target->dataPacket($this->RemovePlayerPacket);
			$this->show = false;
		}
	}

	public function kill(){
		if(!$this->closed){
			if($this->schedule !== null){
				$this->server->getScheduler()->cancelTesk($this->schedule->getTaskId());
				$this->schedule = null;
			}
			$this->closed = true;
			return;
		}
	}
}