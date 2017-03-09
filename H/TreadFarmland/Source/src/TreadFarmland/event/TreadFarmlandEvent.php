<?php

namespace TreadFarmland\event;

use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\event\block\BlockPlaceEvent;
use TreadFarmland\player\TreadFarmlandPlayer;

class TreadFarmlandEvent extends BlockPlaceEvent{
	public function __construct(TreadFarmlandPlayer $player, Block $farmland){
		$blockPlace = Block::get(Block::DIRT);
		$blockPlace->position($farmland);
		parent::__construct($player, $blockPlace, $farmland, $farmland, Item::get(0, 0, 0));
	}
}