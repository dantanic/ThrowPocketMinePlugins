<?php

namespace MineFarm;

class MineFarmGenerator extends \pocketmine\level\generator\Generator{
	private $level, $chunk, $random, $populators = [], $structure, $chunks, $options, $floorLevel, $preset;

	public function getSettings(){
		return [];
	}

	public function getName(){
		return "minefarm";
	}

	public function __construct(array $option = []){}

	public function init(\pocketmine\level\ChunkManager $level, \pocketmine\utils\Random $random){
		$this->level = $level;
		$this->random = $random;
	}

	public function generateChunk($chunkX, $chunkZ){
		$chunk = clone $this->level->getChunk($chunkX, $chunkZ);
		$chunk->setGenerated();
 		for($xx = 0, $x = $chunkX * 16, $farmX = $x % 100; $xx < 16; $xx++, $x++, $farmX++){
			for($zz = 0, $z = $chunkZ * 16, $farmZ = $z % 100; $zz < 16; $zz++, $z++, $farmZ++){
				if($x < 0 || $z < 0 || $x > 999 || $z > 999){
					if($x == -1 || $z == -1 || $x == 1000 || $z == 1000){
						for($y = 0; $y < 128; $y++){
							$chunk->setBlock($xx, $y, $zz, 95, 0);
						}
					}
				}else{
					$chunk->setBiomeColor($xx, $zz, 146, 188, 88);
					if($farmX == 0 || $farmZ == 0 || $farmX == 99 || $farmZ == 99){
						for($y = 0; $y < 128; $y++){
							$chunk->setBlock($xx, $y, $zz, 95, 0);
						}
					}else{
						if($farmX >= 45 && $farmZ >= 45 && $farmX <= 54 && $farmZ <= 54){
							if(($farmX == 49 || $farmX == 50) && ($farmZ == 49 || $farmZ == 50)){
								$chunk->setBlock($xx, 0, $zz, 7, 0);
								$chunk->setBlock($xx, 1, $zz, 35, 12);
								$chunk->setBlock($xx, 2, $zz, 35, 12);
								$chunk->setBlock($xx, 3, $zz, 89, 0);
							}else{
								$chunk->setBlock($xx, 0, $zz, 35, 12);
								$chunk->setBlock($xx, 1, $zz, 35, 12);
								$chunk->setBlock($xx, 2, $zz, 35, 12);
								$chunk->setBlock($xx, 3, $zz, 2, 0);
							}
						}
					}
				}
			}
		}
		$this->level->setChunk($chunkX, $chunkZ, $chunk);
	}

	public function populateChunk($chunkX, $chunkZ){}

	public function getSpawn(){
		return new \pocketmine\math\Vector3();
	}
}