<?php
namespace Flashlight\task;

use pocketmine\scheduler\AsyncTask;

class FlashlightAsyncTask extends AsyncTask{
	public function __construct(){
	}

	public function onCompletion(\pocketmine\Server $server){
		$server->getPluginManager()->getPlugin("Flashlight")->checkFlashlight();
	}

	public function onRun(){
	}
}