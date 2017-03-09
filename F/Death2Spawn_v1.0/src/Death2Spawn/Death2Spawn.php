<?php

namespace Death2Spawn;

class Death2Spawn extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
 	}

	public function onPlayerDeath(\pocketmine\event\player\PlayerDeathEvent $event){
		$player = $event->getEntity();
		$player->teleport($player->getSpawn());
	}
}