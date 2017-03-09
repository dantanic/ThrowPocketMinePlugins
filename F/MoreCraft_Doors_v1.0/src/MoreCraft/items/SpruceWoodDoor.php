<?php

namespace MoreCraft\items;

use MoreCraft\Doors;

class SpruceWoodDoor extends \pocketmine\item\Item{
	public function __construct($meta = 0, $count = 1){
		$this->block = \pocketmine\block\Block::get(Doors::SPRUCE_WOODEN_DOOR_BLOCK);
		parent::__construct(Doors::SPRUCE_WOODEN_DOOR_ITEM, 0, $count, "Spruce Wooden Door");
	}

	public function getMaxStackSize(){
		return 1;
	}
}