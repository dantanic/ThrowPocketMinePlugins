<?php

namespace MoreCraft\blocks;

use MoreCraft\Doors;

class DarkOakWoodDoor extends \pocketmine\block\WoodDoor{
	protected $id = Doors::DARK_OAK_WOODEN_DOOR_BLOCK;

	public function getName(){
		return "DarkOak Wood Door Block";
	}

	public function getDrops(\pocketmine\item\Item $item){
		return [
			[Doors::DARK_OAK_WOODEN_DOOR_ITEM, 0, 1],
		];
	}
}