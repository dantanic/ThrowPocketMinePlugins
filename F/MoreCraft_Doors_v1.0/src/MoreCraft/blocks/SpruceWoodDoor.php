<?php

namespace MoreCraft\blocks;

use MoreCraft\Doors;

class SpruceWoodDoor extends \pocketmine\block\WoodDoor{
	protected $id = Doors::SPRUCE_WOODEN_DOOR_BLOCK;

	public function getName(){
		return "Spruce Wood Door Block";
	}

	public function getDrops(\pocketmine\item\Item $item){
		return [
			[Doors::SPRUCE_WOODEN_DOOR_ITEM, 0, 1],
		];
	}
}