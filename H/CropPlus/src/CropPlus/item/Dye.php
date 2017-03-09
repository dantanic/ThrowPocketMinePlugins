<?php

namespace CropPlus\item;

use pocketmine\item\Item;
use pocketmine\block\Block;
use CropPlus\CropPlus;

class Dye extends Item{
	public function __construct($meta = 0, $count = 1){
		if($meta == 3){
			$this->block = Block::get(CropPlus::COCOA_BEANS_BLOCK);
		}
		parent::__construct(Item::DYE, $meta, $count, "Dye");
	}
}