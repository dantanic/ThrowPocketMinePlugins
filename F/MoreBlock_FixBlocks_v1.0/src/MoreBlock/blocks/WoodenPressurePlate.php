<?php

namespace MoreBlock\blocks;

use MoreBlock\FixBlocks;

class WoodenPressurePlate extends \pocketmine\block\Block{
	protected $id = FixBlocks::WOODEN_PRESSURE_PLATE;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getName(){
		return "WoodenPressurePlate";
	}

	public function canBeFlowedInto(){
		return false;
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

	public function getDrops(\pocketmine\item\Item $item){
		return [[$this->id, 0, 1]];
	}
}