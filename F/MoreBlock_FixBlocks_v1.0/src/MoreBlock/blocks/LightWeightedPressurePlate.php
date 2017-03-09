<?php

namespace MoreBlock\blocks;

use MoreBlock\FixBlocks;

class LightWeightedPressurePlate  extends \pocketmine\block\Block{
	protected $id = FixBlocks::LIGHT_WEIGHTED_PRESSURE_PLATE;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getName(){
		return "LightWeightedPressurePlate";
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