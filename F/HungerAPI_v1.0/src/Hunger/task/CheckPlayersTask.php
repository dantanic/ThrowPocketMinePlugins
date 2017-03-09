<?php
namespace Hunger\task;

use pocketmine\scheduler\PluginTask;

class CheckPlayersTask extends PluginTask{
	protected $owner;

 	public function onRun($currentTick){
		$this->owner->checkPlayers();
	}
}