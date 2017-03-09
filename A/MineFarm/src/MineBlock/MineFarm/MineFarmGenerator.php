<?php

namespace MineBlock\MineFarm;

use pocketmine\level\generator\Generator;
use pocketmine\level\generator\GenerationChunkManager;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\block\Block;
use pocketmine\level\ChunkManager;

class MineFarmGenerator extends Generator{
	private $level, $chunk, $random, $populators = [], $structure, $chunks, $options, $floorLevel, $preset;

	public function getSettings(){
		return [];
	}

	public function getName(){
		return "minefarm";
	}

	public function __construct(array $option = []){}

	public function init(ChunkManager $level, Random $random){
		$this->level = $level;
		$this->random = $random;
//		$this->plugin = MineFarm::getInstance();
	}

	public function generateChunk($chunkX, $chunkZ){
		$chunk = clone $this->level->getChunk($chunkX, $chunkZ);
		$chunk->setGenerated();
//		$plugin = $chunk->getLevel()->getServer()->getPluginManager()->getPlugin("MineFarm");
		if(true){//$plugin->isFarm($chunk) && $l = $plugin->isLand($chunk)){
$l = true;
			$m = true;//$plugin->isMain($chunk);
//			$list = [0 => 7, 1 => 7, 2 => 2, 7 => 98, 8 => 3, 9 => 2];
			$list = [0 => 95, 1 => 95, 2 => 2, 7 => 98, 8 => 12, 9 => 13];
			for($y = 0; $y < 10; $y++){
				for($x = 0; $x < 16; $x++){
					for($z = 0; $z < 16; $z++){
//						$id = $l ? (isset($list[$y]) ? $list[$y] : (!$m ? 3 : 0)) : 0;
						$id = $l ? (isset($list[$y]) ? ($y < 8 ? $list[$y] : 35) : (!$m ? 3 : 0)) : 0;
//						$dmg = 0;
						$dmg = $y < 8 ? 0 : $list[$y];
						if($y < 8 && ($x == 0 || $x == 15 || $z == 0 || $z == 15)) $id = 98;
						if($m && $y > 2 && $y < 7){
							if(($x == 5 || $x == 10) && ($z == 5 || $z == 10)) $id = 17;
							elseif($x > 4 && $x < 11 && $z > 4 && $z < 11) $id = 1;
						}
						if(($y == 2 || $y == 7) && in_array($x,$a = [1,4,7,8,11,14]) && in_array($z,$a)) $id = 89;
						if($m && $y > 2 && $y < 10 && ($x == 1 || $x == 14) && ($z == 1 || $z == 14)){
							if($y % 2 == 1){
								$id = 8;
								$dmg = 23;
							}else{
								$id = 0;
							}
						}
						$chunk->setBlock($x,$y,$z,$id,$dmg);
					}
				}
			}
		}else{
			for($y = 0; $y < 3; $y++){
				for($x = 0; $x < 16; $x++){
					for($z = 0; $z < 16; $z++){
						$chunk->setBlock($x,0,$z,95,0);
					}
	 			}
			}			
		}
		$this->level->setChunk($chunkX, $chunkZ, $chunk);
	}

	public function populateChunk($chunkX, $chunkZ){}

	public function getSpawn(){
		return new Vector3(0, 9, 0);
	}
}