<?php

namespace CropPlus\block;

use CropPlus\CropPlus;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\Server;
use pocketmine\level\Level;
use pocketmine\event\block\BlockGrowEvent;

class NetherWart extends Crops{
	protected $id = CropPlus::NETHER_WART_BLOCK;

	public function getName(){
		return "NetherWart Block";
	}

	public function canBeActivated(){
		return false;
	}

 	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		if($block->getSide(0)->getID() === self::SOUL_SAND){
			$this->getLevel()->setBlock($block, $this, true, true);
			return true;
		}
		return false;
	}

 	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			if($this->getSide(0)->getID() !== self::SOUL_SAND){
				$this->getLevel()->useBreakOn($this);
				return Level::BLOCK_UPDATE_NORMAL;
			}
		}elseif($type === Level::BLOCK_UPDATE_RANDOM){
			if($this->meta < 0x03 && mt_rand(1, 6) === 1){ // 1/6
				$block = clone $this;
				$block->meta++;
				Server::getInstance()->getPluginManager()->callEvent($ev = new BlockGrowEvent($this, $block));
				if(!$ev->isCancelled()){
					$this->getLevel()->setBlock($this, $ev->getNewState(), true, true);
				}
			}
			return Level::BLOCK_UPDATE_RANDOM;
		}
		return false;
	}

	public function getDrops(Item $item){
		$drops = [];
		if($this->meta >= 0x03){
			$drops[] = [CropPlus::NETHER_WART_SEEDS, 0, mt_rand(1, 4)];
		}else{
			$drops[] = [CropPlus::NETHER_WART_SEEDS, 0, 1];
		}
		return $drops;
	}
}