<?php
namespace FishingAPI\entity;

use pocketmine\entity\Item as DroppedItem;
use pocketmine\level\format\FullChunk;
use pocketmine\Player;
use pocketmine\nbt\tag\Compound as CompoundTag;
use pocketmine\network\protocol\SetEntityLinkPacket;

class FishItemEntity extends DroppedItem{
	private $fishingHook;

 	public function __construct(FullChunk $chunk, CompoundTag $nbt, FishingHook $fishingHook = null){
 		if($fishingHook === null){
 			$this->kill();
 			return;
 		}
		parent::__construct($chunk, $nbt);
		$this->fishingHook = $fishingHook;
		$this->server = \pocketmine\Server::getInstance();
	}


 	public function onUpdate($currentTick){
 		parent::onUpdate($currentTick);
 		$player = $this->fishingHook->shootingEntity;
 		$player->sendMessage(
			"x : " . round($this->x,2) .
			"y : " . round($this->y,2) .
			"z : " . round($this->z,2) .
 			"distance : " . $this->distance($player)
		);

	}
}