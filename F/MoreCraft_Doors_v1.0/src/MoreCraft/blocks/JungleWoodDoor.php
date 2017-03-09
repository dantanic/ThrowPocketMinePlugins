<?php

namespace MoreCraft\blocks;

use MoreCraft\Doors;

class JungleWoodDoor extends \pocketmine\block\WoodDoor{
	protected $id = Doors::JUNGLE_WOODEN_DOOR_BLOCK;

	public function getName(){
		return "Jungle Wood Door Block";
	}

	public function getDrops(\pocketmine\item\Item $item){
		return [
			[Doors::JUNGLE_WOODEN_DOOR_ITEM, 0, 1],
		];
	}
}