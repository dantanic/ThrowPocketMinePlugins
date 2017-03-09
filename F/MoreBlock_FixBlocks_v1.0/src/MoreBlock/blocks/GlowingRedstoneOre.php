<?php

namespace MoreBlock\blocks;

use MoreBlock\FixBlocks;

class GlowingRedstoneOre extends \pocketmine\block\GlowingRedstoneOre{
	public function getHardness(){
		return 0.5;
	}
}