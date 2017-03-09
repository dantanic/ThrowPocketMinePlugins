<?php
namespace FarmBooster\task;

use pocketmine\Server;
use pocketmine\scheduler\AsyncTask;

class DoChunksTickAsyncTask extends AsyncTask{
	const FARMS = 0;
	const PLAYERS = 1;

	private $boosters, $levelTickBlocks, $farmData, $onUpdateBlocks;

	public function __construct($boosters, $levelTickBlocks, $farmData){
		$this->boosters = $boosters;
		$this->levelTickBlocks = $levelTickBlocks;
		$this->farmData = $farmData;
		if(version_compare("7.0", PHP_VERSION) <= 0){
			$this->onUpdateBlocks = new \Threaded();			
		}
	}

	public function onCompletion(Server $server){
		$server->getPluginManager()->getPlugin("FarmBooster")->doChunksTickCallback((array) $this->onUpdateBlocks);
 	}

	public function onRun(){
		$keys = array_keys((array) $this->levelTickBlocks);
		if(version_compare("7.0", PHP_VERSION) > 0){
			$onUpdateBlocks = [];
		}
		for($i = mt_rand(0, 19); $i < count($keys); $i += mt_rand(1, 20)){
			$vec = $this->levelTickBlocks[$keys[$i]];
			$x = (int) $vec->x - ($vec->x % 110);
			$z = (int) $vec->z - ($vec->z % 110);
			$farmX =	$x == 0 ? 0 : $x / 110;
			$farmZ = $z == 0 ? 0 : $z / 110;
			$index = $farmX + $farmZ * 30;
			if(isset($this->farmData[self::FARMS][$index]) &&
				isset($this->boosters[$this->farmData[self::FARMS][$index]])
			){
				if(version_compare("7.0", PHP_VERSION) > 0){
					$onUpdateBlocks[$keys[$i]] = $vec;
				}else{
					$this->onUpdateBlocks[$keys[$i]] = $vec;
				}
			}
		}
		if(version_compare("7.0", PHP_VERSION) > 0){
			$this->onUpdateBlocks = $onUpdateBlocks;
		}
	}
}