<?php

namespace Fishing;

class FishingRod extends \pocketmine\item\Tool{
	public function __construct($meta = 0, $count = 1){
		parent::__construct(346, $meta, $count, "Fishing Rod");
	}

	public function getMaxDurability(){
		return 360;
	}

	public function getMaxStackSize(){
		return 1;
	}
}