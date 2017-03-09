<?php

namespace MoreCraft\items;

use MoreCraft\Doors;

class BirchWoodDoor extends \pocketmine\item\Item{
	public function __construct($meta = 0, $count = 1){
		$this->block = \pocketmine\block\Block::get(Doors::BIRCH_WOODEN_DOOR_BLOCK);
		parent::__construct(Doors::BIRCH_WOODEN_DOOR_ITEM, 0, $count, "Birch Wooden Door");
	}

	public function getMaxStackSize(){
		return 1;
	}
}