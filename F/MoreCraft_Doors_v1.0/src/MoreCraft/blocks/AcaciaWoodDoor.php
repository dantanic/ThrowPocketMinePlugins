<?php

namespace MoreCraft\blocks;

use MoreCraft\Doors;

class AcaciaWoodDoor extends \pocketmine\block\WoodDoor{
	protected $id = Doors::ACACIA_WOODEN_DOOR_BLOCK;

	public function getName(){
		return "Acacia Wood Door Block";
	}

	public function getDrops(\pocketmine\item\Item $item){
		return [
			[Doors::ACACIA_WOODEN_DOOR_ITEM, 0, 1],
		];
	}
}