<?php

namespace CropPlus\block;

use CropPlus\CropPlus;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\Server;
use pocketmine\level\Level;
use pocketmine\event\block\BlockGrowEvent;

class CocoaBeans extends Crops{
	protected $id = CropPlus::COCOA_BEANS_BLOCK;

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$metaList = [2 => 0, 3 => 2, 4 => 3, 5 => 1];
		if(isset($metaList[$face])){
			$this->meta = $metaList[$face];
			$side = $this->getSide([3, 4, 2, 5][$this->meta]);
			if($side->getID() === self::WOOD && $side->getDamage() === 3){
				$this->getLevel()->setBlock($block, $this, true, true);
				return true;
			}
		}
		return false;
	}

	public function onActivate(Item $item, Player $player = null){
		if($this->meta < $this->meta % 4 + 8 && $item->getID() === Item::DYE && $item->getDamage() === 0x0F){ //Bonemeal
			$block = clone $this;
			$block->meta += 4 * mt_rand(1, 2);
			if($block->meta >= $this->meta % 4 + 8){
				$block->meta = $this->meta % 4 + 8;
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
			$side = $this->getSide([3, 4, 2, 5][$this->meta % 4]);
			if(!($side->getID() === self::WOOD && $side->getDamage() === 3)){
				$this->getLevel()->useBreakOn($this);
				return Level::BLOCK_UPDATE_NORMAL;
			}
		}elseif($type === Level::BLOCK_UPDATE_RANDOM){
			if($this->meta < $this->meta % 4 + 8 && mt_rand(1, 5) === 1){ // 1/5
				$block = clone $this;
				$block->meta += 4;
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
		if($this->meta >= $this->meta % 4 + 8){
			$drops[] = [Item::DYE, 3, mt_rand(1, 3)];
		}else{
			$drops[] = [Item::DYE, 3, 1];
		}
		return $drops;
	}
}