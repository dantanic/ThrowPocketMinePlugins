<?php

namespace MineBlock\Test;

use pocketmine\plugin\PluginBase;

class Test extends PluginBase{

	public function onEnable(){
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this, "onTick"]), 20);
		$this->player = [];
	}

	public function onTick(){
		foreach($this->getServer()->getOnlinePlayers() as $p){
			if($p->dead) continue;
			if(!isset($this->player[$n = $p->getName()]) || $this->player[$n]->closed == true){
				$this->player[$n] = new FroatingText($this, $p);
			}
		}
	}
}