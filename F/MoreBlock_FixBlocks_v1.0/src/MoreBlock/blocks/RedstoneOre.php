<?php

namespace MoreBlock\blocks;

use MoreBlock\FixBlocks;

class RedstoneOre extends \pocketmine\block\RedstoneOre{
	public function getHardness(){
		return 0.5;
	}
}