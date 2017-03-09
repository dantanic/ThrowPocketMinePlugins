<?php

namespace MoreBlock\blocks;

use MoreBlock\FixBlocks;

class Slab extends \pocketmine\block\Slab{
	public function getHardness(){
		return 0.5;
	}
}