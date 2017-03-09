<?php

namespace MoreBlock\blocks;

use MoreBlock\FixBlocks;

class NetherPortalBlock extends \pocketmine\block\Block{
	protected $id = FixBlocks::NETHER_PORTAL;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getName(){
		return "Nether Portal";
	}

	public function canBeFlowedInto(){
		return true;
	}

	public function getHardness(){
		return 0;
	}

	public function getResistance(){
		return 0;
	}

	public function isSolid(){
		return false;
	}

	public function getBoundingBox(){
		return null;
	}
}