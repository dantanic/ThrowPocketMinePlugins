<?php

namespace MoreCraft\blocks;

use MoreCraft\Doors;

class BirchWoodDoor extends \pocketmine\block\WoodDoor{
	protected $id = Doors::BIRCH_WOODEN_DOOR_BLOCK;

	public function getName(){
		return "BiRch Wood Door Block";
	}

	public function getDrops(\pocketmine\item\Item $item){
		return [
			[Doors::BIRCH_WOODEN_DOOR_ITEM, 0, 1],
		];
	}
}