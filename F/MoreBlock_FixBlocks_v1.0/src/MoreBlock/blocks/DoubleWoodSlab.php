<?php

namespace MoreBlock\blocks;

use MoreBlock\FixBlocks;

class DoubleWoodSlab extends \pocketmine\block\DoubleWoodSlab{
	public function getHardness(){
		return 0.5;
	}
}