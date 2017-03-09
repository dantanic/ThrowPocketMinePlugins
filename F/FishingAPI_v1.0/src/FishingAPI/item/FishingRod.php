<?php
namespace FishingAPI\item;

use pocketmine\item\Tool;

class FishingRod extends Tool{
	public function __construct($meta = 0, $count = 1){
		parent::__construct(346, $meta, $count, "Fishing Rod");
	}

	public function getMaxDurability(){
		return 360;
	}

	public function getMaxStackSize(){
		return 1;
	}

/*
	public function canBeActivated(){
		return true;
	}

	public function onActivate(Level $level, Player $player, $block, $target, $face, $fx, $fy, $fz){
		foreach($player->getLevel()->getEntities() as $entity){
			if($entity instanceof FishingHook){
				if($entity->shootingEntity === $player) $entity->reelLine();
			}
		}
	}
*/
}