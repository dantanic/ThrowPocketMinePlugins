<?php

namespace CropPlus\block;

use pocketmine\block\Block;
use pocketmine\block\Farmland as PMFarmland;
use pocketmine\level\Level;

class Farmland extends PMFarmland{
	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_RANDOM){
			$level = $this->getLevel();
			$this->meta = 0;
			for($x = $this->x - 4; $x <= $this->x + 4; $x++){
				for($z = $this->z - 4; $z <= $this->z + 4; $z++){
					if(in_array($level->getBlockIdAt($x, $this->y, $z), [Block::WATER, Block::STILL_WATER])){
						$this->meta = 1;
						break 2;
					}
				}
			}
			if($this->meta == 0){
				$this->getLevel()->setBlock($this, Block::get(Block::DIRT), true, true);
			}else{
				$this->getLevel()->setBlock($this, $this, true, true);
			}
			return Level::BLOCK_UPDATE_RANDOM;
		}
		return false;
	}
}