<?php

namespace MineFarm;

use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\block\Block;
use pocketmine\utils\Config;

class MineFarmGenerator extends \pocketmine\level\generator\Generator{
	private $mf, $level, $chunk, $random, $populators = [], $structure, $chunks, $options, $floorLevel, $preset;

	public function getSettings(){
		return [];
	}

	public function getName(){
		return "minefarm";
	}

	public function __construct(array $option = []){}

	public function init(\pocketmine\level\ChunkManager $level, Random $random){
		$this->mf = (new Config(\pocketmine\PLUGIN_PATH . "MineFarm/Farm.yml", Config::YAML, ["Auto" => false, "Sell" => true, "Price" => 100000, "Distance" => 5, "Size" => 1, "Air" => 3, "MineWorld" => "Mine", "MineBlock" => "48:0", "Item" => true, "Items" => [["269:0", 1], ["270:0", 1], ["271:0", 1], ["290:0", 1]], "Farm" => [], "Invite" => []]))->getAll();
		$this->level = $level;
		$this->random = $random;
	}

	public function isFarm($farm){
		$x = $farm->getX();
		$z = $farm->getZ();
		$dd = $this->mf["Size"] + $this->mf["Air"];
		$d = $this->mf["Distance"] + 1 + $dd;
		return $x >= 0 && $x % $d <= $dd && $z >= 0 && $z % $d <= $dd;
	}

	public function isLand($farm){
		$x = $farm->getX();
		$z = $farm->getZ();
		$dd = $this->mf["Size"];
		$d = $this->mf["Distance"] + 1 + $this->mf["Air"] + $dd;
		return $x >= 0 && $x % $d < $dd && $z >= 0 && $z % $d < $dd;
	}

	public function isMain($farm){
		$x = $farm->getX();
		$z = $farm->getZ();
		$d = $this->mf["Distance"] + 1 + $this->mf["Size"] + $this->mf["Air"];
		return $x >= 0 && $x % $d === 0 && $z >= 0 && $z % $d === 0;
	}

	public function generateChunk($chunkX, $chunkZ){
		$chunk = clone $this->level->getChunk($chunkX, $chunkZ);
		$chunk->setGenerated();
		if($this->isFarm($chunk) && $this->isLand($chunk)){
/*
			for($x = 0; $x < 16; $x++){
				for($z = 0; $z < 16; $z++){
					$chunk->setBlock($x,0,$z,35,12);
					$chunk->setBlock($x,1,$z,35,13);
 				}
			}
		}
*/
			$m = $this->isMain($chunk);
			$list = [95, 95, 2, 0, 0, 0, 0, 98, 12, 13];
			for($y = 0; $y < 10; $y++){
				for($x = 0; $x < 16; $x++){
					for($z = 0; $z < 16; $z++){
						$chunk->setBiomeColor($x, $z, rand(0,255), rand(0,255), rand(0,255));
						if($m){
							$id = $y < 8 ? $list[$y] : 35;
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
								}else $id = 0;
							}
						}else{
							if($y < 8) $id = $dmg = 0;
							else{
								$id = 35;
								$dmg = $list[$y];
							}
						}
						if($id !== 0) $chunk->setBlock($x,$y,$z,$id,$dmg);
					}
				}
			}
		}
/*
		else{
			for($y = 0; $y < 3; $y++){
				for($x = 0; $x < 16; $x++){
					for($z = 0; $z < 16; $z++){
						$chunk->setBlock($x,0,$z,95,0);
					}
	 			}
			}			
		}
*/
		$this->level->setChunk($chunkX, $chunkZ, $chunk);
	}

	public function populateChunk($chunkX, $chunkZ){}

	public function getSpawn(){
		return new Vector3(0, 9, 0);
	}
}