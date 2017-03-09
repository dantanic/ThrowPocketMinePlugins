<?php

namespace TreadFarmland\player;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\block\Block;
use TreadFarmland\event\TreadFarmlandEvent;

class TreadFarmlandPlayer extends Player{
 	public function fall($fallDistance){
		parent::fall($fallDistance);
 		$down = $this->level->getBlock($this->subtract(0, 1, 0)->floor());
 		if($down->getID() == Block::FARMLAND){
			Server::getInstance()->getPluginManager()->callEvent($ev = new TreadFarmlandEvent($this, $down));
			if(!$ev->isCancelled()){
				$this->getLevel()->setBlock($ev->getBlock(), $ev->getBlock(), true, true);
			}
		}
	}
}