<?php

namespace Chair;

use pocketmine\entity\Entity;

class Chair extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onLoad(){
		$this->AddEntityPacket = new \pocketmine\network\protocol\AddEntityPacket();
		$this->AddEntityPacket->type = 14;
		$this->AddEntityPacket->metadata = [
 			Entity::DATA_NO_AI => [Entity::DATA_TYPE_BYTE, 1],
 		];
 		$this->RemoveEntityPacket = new \pocketmine\network\protocol\RemoveEntityPacket();
		$this->MovePlayerPacket = new \pocketmine\network\protocol\MovePlayerPacket();
		$this->MovePlayerPacket->yaw = 0;
		$this->MovePlayerPacket->bodyYaw = 0;
		$this->MovePlayerPacket->pitch = 0;
		$this->SetEntityLinkPacket = new SetEntityLinkPacket();
		$this->SetEntityLinkPacket->linkType = 1;
	 	$this->SetEntityLinkPacket->ridden = $this->AddEntityPacket->eid = $this->RemoveEntityPacket->eid = $this->MovePlayerPacket->eid = $this->eid = bcadd("1095216660480", mt_rand(0, 0x7fffffff));
		$this->players = [];
		$this->vecTable = [];
		for($yaw = 0; $yaw < 360; $yaw++){
			for($pitch = -180; $pitch < 180; $pitch++){
				$this->vecTable[$yaw . ":" . $pitch] = [
					sin($yaw/ 180 * M_PI) * cos($pitch / 180 * M_PI) * -2,
					min(2, max(0, 1.2 + sin($pitch / 180 * M_PI) * -2)),
					cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 2
				];
			}
		}
	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this,"onTick"]), 1);
	}


	public function onDataPacketReceive(\pocketmine\event\server\DataPacketReceiveEvent $event){
		$packet = $event->getPacket();
		$player = $event->getPlayer();
		$player->sendMessage("0x" . dechex($packet->pid()));
		if($packet->pid() == 0x9a && $packet->action == 3){ //Unride
			unset($this->players[$player->getName()]);
 		}
	}

	public function onTick(){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$name = $player->getName();
			if(!$player->spawned){
				if(isset($this->players[$name])){
					$player->dataPacket($this->RemoveEntityPacket);
					unset($this->players[$name]);
				}
			}else{
				$vec = $this->vecTable[round($player->yaw) . ":" . round($player->pitch)];
				$this->AddEntityPacket->x = $this->MovePlayerPacket->x = $player->x + $vec[0];
				$this->AddEntityPacket->y = $this->MovePlayerPacket->y = floor($player->y);// + 0.3; //+ $vec[1];
				$this->AddEntityPacket->z = $this->MovePlayerPacket->z = $player->z + $vec[2];
				$this->MovePlayerPacket->yaw = $player->yaw;
//				$this->MovePlayerPacket->pitch = $player->pitch;
				$player->dataPacket($this->MovePlayerPacket);
				if(!isset($this->players[$name])){
					$this->players[$name] = true;
					$player->setAllowFlight(true);
 					$player->dataPacket($this->AddEntityPacket);
					$this->SetEntityLinkPacket->rider = 0; // $player->getID();
					$player->dataPacket($this->SetEntityLinkPacket);
				}
			}
/*
			$vec = $this->vecTable[round($player->yaw) . ":" . round($player->pitch)];
			$this->AddEntityPacket->x = $player->x + $vec[0];
			$this->AddEntityPacket->y = $player->y + $vec[1];
			$this->AddEntityPacket->z = $player->z + $vec[2];
			$player->dataPacket($this->AddEntityPacket);
*/
		}
	}
}