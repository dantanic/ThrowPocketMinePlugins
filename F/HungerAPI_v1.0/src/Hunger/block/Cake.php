<?php
namespace Hunger\block;

use pocketmine\block\Cake as PMCake;
use pocketmine\block\Air;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityRegainHealthEvent;
use Hunger\Hunger;

class Cake extends PMCake{
	public function onActivate(Item $item, Player $player = null){
		if(Hunger::getHunger($player) < 19){
			++$this->meta;
			Hunger::saturation($player, 2);
			if($this->meta >= 0x07){
				$this->getLevel()->setBlock($this, new Air(), true);
			}else{
				$this->getLevel()->setBlock($this, $this, true);
			}
			return true;
		}
		return false;
	}
}