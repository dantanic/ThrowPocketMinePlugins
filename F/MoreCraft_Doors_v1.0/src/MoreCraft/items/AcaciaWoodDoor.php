<?php

namespace MoreCraft\items;

use MoreCraft\Doors;

class AcaciaWoodDoor extends \pocketmine\item\Item{
	public function __construct($meta = 0, $count = 1){
		$this->block = \pocketmine\block\Block::get(Doors::ACACIA_WOODEN_DOOR_BLOCK);
		parent::__construct(Doors::ACACIA_WOODEN_DOOR_ITEM, 0, $count, "Acacia Wooden Door");
	}

	public function getMaxStackSize(){
		return 1;
	}
}