<?php

namespace CropPlus\item;

use pocketmine\item\Item;
use pocketmine\block\Block;
use CropPlus\CropPlus;

class NetherWartSeeds extends Item{
	public function __construct($meta = 0, $count = 1){
		$this->block = Block::get(CropPlus::NETHER_WART_BLOCK);
		parent::__construct(CropPlus::NETHER_WART_SEEDS, 0, $count, "NetherWart Seeds");
	}
}