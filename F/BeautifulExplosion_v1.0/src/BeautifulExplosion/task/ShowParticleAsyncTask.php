<?php
namespace BeautifulExplosion\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\level\Level;

class ShowParticleAsyncTask extends AsyncTask{
	public $xyz, $levelName;

	public function __construct($x, $y, $z, $levelName){
		$this->xyz = [$x, $y, $z];
		$this->levelName = $levelName;
	}

	public function onCompletion(Server $server){
		$level = $server->getLevelByName($this->levelName);
		if($level instanceof Level){
			$server->getPluginManager()->getPlugin("BeautifulExplosion")->showParticle($this->xyz[0], $this->xyz[1], $this->xyz[2], $level);
		}
	}

	public function onRun(){
	}
}