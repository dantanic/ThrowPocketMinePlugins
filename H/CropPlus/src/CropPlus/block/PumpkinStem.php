<?php

namespace CropPlus\block;

use pocketmine\item\Item;
use pocketmine\Server;
use pocketmine\level\Level;
use pocketmine\event\block\BlockGrowEvent;

class PumpkinStem extends Crops{
	protected $id = self::PUMPKIN_STEM;

	public function getName(){
		return "Pumpkin Stem";
	}

 	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			if($this->getSide(0)->getID() !== self::FARMLAND){
				$this->getLevel()->useBreakOn($this);
				return Level::BLOCK_UPDATE_NORMAL;
			}
		}elseif($type === Level::BLOCK_UPDATE_RANDOM){
			if($this->meta < 0x07){
				parent::onUpdate(Level::BLOCK_UPDATE_RANDOM);
			}else{
				for($side = 2; $side <= 5; $side++){
					if($this->getSide($side)->getID() === self::PUMPKIN){
						return Level::BLOCK_UPDATE_RANDOM;
					}
				}
				$side = $this->getSide(mt_rand(2, 5));
				if($side->getID() === self::AIR && in_array($side->getSide(0)->getID(), [self::FARMLAND, self::GRASS, self::DIRT])){
					Server::getInstance()->getPluginManager()->callEvent($ev = new BlockGrowEvent($side, self::get(self::PUMPKIN)));
					if(!$ev->isCancelled()){
						$this->getLevel()->setBlock($side, $ev->getNewState(), true);
					}
				}
			}
			return Level::BLOCK_UPDATE_RANDOM;
		}
		return false;
	}

	public function getDrops(Item $item){
		return [
			[Item::PUMPKIN_SEEDS, 0, 1],
		];
	}
}