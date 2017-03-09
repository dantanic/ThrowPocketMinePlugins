<?php

namespace MoreCraft;

use pocketmine\block\Block;

class WaterBottleItem extends \pocketmine\item\Item{
	public function __construct($meta = 0, $count = 1){
		parent::__construct(373, 0, $count, "WaterBottle");
	}

	public function canBeActivated(){
		return true;
	}

 	public function getMaxStackSize(){
		return 1;
	}

 	public function onActivate(\pocketmine\level\Level $level, \pocketmine\Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		$player->getServer()->getPluginManager()->callEvent($ev = new \pocketmine\event\player\PlayerBucketFillEvent($player, $block, $face, $this, $this));
		if(!$ev->isCancelled()){
			$block = $player->getLevel()->getBlock($block);
			if($block->getID() == 8){
				$player->getLevel()->setBlock($block, new Block($block->getDamage() == 0 ? 0 : 8), true, true);
			}elseif($block->getID() == 0){
				$player->getLevel()->setBlock($block, new Block(8), true, true);
			}
		}
		return false;
 	}
}