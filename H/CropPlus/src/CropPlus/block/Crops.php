<?php

namespace CropPlus\block;

use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\block\Crops as PMCrops;
use pocketmine\Server;
use pocketmine\level\Level;
use pocketmine\event\block\BlockGrowEvent;

class Crops extends PMCrops{
	protected $id = self::AIR;

	public function __construct($meta = 0){
		$this->id = (int) $this->id;
		$this->meta = (int) $meta;
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		if($block->getSide(0)->getID() === self::FARMLAND){
			$this->getLevel()->setBlock($block, $this, true, true);
			return true;
		}
		return false;
	}

	public function onActivate(Item $item, Player $player = null){
		if($this->meta < 0x07 && $item->getID() === Item::DYE && $item->getDamage() === 0x0F){ //Bonemeal
			$block = clone $this;
			$block->meta += mt_rand(2, 5);
			if($block->meta > 0x07){
				$block->meta = 0x07;
			}
			Server::getInstance()->getPluginManager()->callEvent($ev = new BlockGrowEvent($this, $block));
			if(!$ev->isCancelled()){
				$this->getLevel()->setBlock($this, $ev->getNewState(), true, true);
				if($player->isSurvival()){
					$item->count--;
				}
				return true;
			}
		}
		return false;
	}

 	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			if($this->getSide(0)->getID() !== self::FARMLAND){
				$this->getLevel()->useBreakOn($this);
				return Level::BLOCK_UPDATE_NORMAL;
			}
		}elseif($type === Level::BLOCK_UPDATE_RANDOM){
			if($this->meta < 0x07 && ($this->getSide(0)->getDamage() == 1 ? mt_rand(1, 2) : mt_rand(1, 3)) === 1){ // If wet farm : 1/2, else : 1/3
				$block = clone $this;
				$block->meta += $this->getSide(0)->getDamage() == 1 ? 2 : 1;
				Server::getInstance()->getPluginManager()->callEvent($ev = new BlockGrowEvent($this, $block));
				if(!$ev->isCancelled()){
					$this->getLevel()->setBlock($this, $ev->getNewState(), true, true);
				}
			}
			return Level::BLOCK_UPDATE_RANDOM;
		}
		return false;
	}
}