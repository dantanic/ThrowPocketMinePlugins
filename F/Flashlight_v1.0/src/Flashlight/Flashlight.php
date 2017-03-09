<?php
namespace Flashlight;

use pocketmine\plugin\PluginBase;
use pocketmine\math\Vector3;
use Flashlight\task\FlashlightTask;

class Flashlight extends PluginBase{
	private $lights = [];

	public function onEnable(){
 		$this->getServer()->getScheduler()->scheduleRepeatingTask(new FlashlightTask($this), 5);
	}

	public function checkFlashlight(){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$player->sendMessage(
				$player->level->getBlockLightAt($x = (int) $player->x, $y = (int) $player->y, $z = (int) $player->z) . 
				" : " . 
				$player->level->getBlockSkyLightAt($x, $y, $z));
/*
			$lightLevel = $player->getInventory()->getItemInHand()->getBlock()->getLightLevel();
			if($lightLevel === 0){
				if(isset($this->lights[$name = $player->getName()])){
					$this->lights[$name][1][3] = $this->lights[$name][0]->getBlock(new Vector3(...$this->lights[$name][1]))->getLightLevel();
					$this->lights[$name][0]->setBlockLightAt(...$this->lights[$name][1]);
					$this->lights[$name][0]->updateBlockLight(...$this->lights[$name][1]);
					$this->lights[$name][0]->setBlockSkyLightAt(...$this->lights[$name][1]);
					unset($this->lights[$name]);
				}
			}else{
				if(isset($this->lights[$name = $player->getName()])){
					if($player->level !== $this->lights[$name][0] ||
						floor($player->x) !== $this->lights[$name][1][0] || 
						floor($player->y + 1) !== $this->lights[$name][1][1] || 
						floor($player->z) !== $this->lights[$name][1][2] 
					){
						$this->lights[$name][1][3] = $this->lights[$name][0]->getBlock(new Vector3(...$this->lights[$name][1]))->getLightLevel();
						$this->lights[$name][0]->setBlockLightAt(...$this->lights[$name][1]);
						$this->lights[$name][0]->updateBlockLight(...$this->lights[$name][1]);
						$this->lights[$name][0]->setBlockSkyLightAt(...$this->lights[$name][1]);
					}
				}
				$this->lights[$name = $player->getName()][0] = $player->level;
				$this->lights[$name][1] = [$x = floor($player->x), $y = floor($player->y + 1), $z = floor($player->z)];
				$this->lights[$name][0]->setBlockLightAt($x, $y, $z, $lightLevel);
				$this->lights[$name][0]->updateBlockLight($x, $y, $z, $lightLevel);
				$this->lights[$name][0]->setBlockSkyLightAt($x, $y, $z, $lightLevel);
			}
*/
		}
	}
}