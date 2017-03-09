<?php

namespace FlashLight;

use pocketmine\plugin\PluginBase;
use pocketmine\item\Item as ID;
use pocketmine\level\Position as Pos;
use pocketmine\block\Block;

class FlashLight extends PluginBase{

	public function onEnable(){
		$this->player = [];
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this, "onTick"]), 5);
	}

	public function onTick(){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$pos = new Pos(floor($player->x) + 0.5, floor($player->y -0.3), floor($player->z) + 0.5, $player->getLevel());
 			if(!isset($this->player[$name = $player->getName()])) $this->player[$name] = [$pos, false, false];
			if($pos->getLevel() !== $this->player[$name][0]->getLevel() || $pos->distance($this->player[$name][0]) > 0){
				$item = $player->getInventory()->getItemInHand();
				if($this->player[$name][1] !== false) $this->player[$name][0]->getLevel()->setBlockLightAt($this->player[$name][0]->x, $this->player[$name][0]->y, $this->player[$name][0]->z, $this->player[$name][1]);
				if($this->player[$name][2] !== false) $this->player[$name][0]->getLevel()->setBlockSkyLightAt($this->player[$name][0]->x, $this->player[$name][0]->y, $this->player[$name][0]->z, $this->player[$name][2]);
				if(($item->getID() == ID::TORCH || $item->getID() == ID::GLOWSTONE || $item->getID() == ID::BUCKET && ($item->getDamage() == 10 || $item->getDamage() == 11))){
					$this->player[$name] = [$pos, $pos->getLevel()->getBlockLightAt($pos->z, $pos->y, $pos->z), $pos->getLevel()->getBlockSkyLightAt($pos->z, $pos->y, $pos->z)];
					$pos->getLevel()->setBlockLightAt($pos->z, $pos->y, $pos->z, 15);
					$pos->getLevel()->setBlockSkyLightAt($pos->z, $pos->y, $pos->z, 15);
				}else{
					$this->player[$name] = [new Pos(0, 0, 0, $pos->getLevel()), false, false];
				}
			}
		}
	}
}