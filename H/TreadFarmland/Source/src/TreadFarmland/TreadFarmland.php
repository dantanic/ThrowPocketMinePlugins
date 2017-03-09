<?php

namespace TreadFarmland;


use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\block\BlockPlaceEvent;
use TreadFarmland\player\TreadFarmlandPlayer;

class TreadFarmland extends PluginBase implements Listener{
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlayerCreation(PlayerCreationEvent $event){
		$event->setPlayerClass(TreadFarmlandPlayer::class);
	}

/*
	Test for Handle TreadFarmlandEvent

	public function onBlockPlace(BlockPlaceEvent $event){
		if($event instanceof TreadFarmlandEvent){
			echo "Handle TreadFarmlandEvent";
		}
	}
*/
}