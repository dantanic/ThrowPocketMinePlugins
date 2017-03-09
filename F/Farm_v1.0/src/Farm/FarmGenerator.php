<?php

namespace Farm;

class FarmGenerator extends \pocketmine\level\generator\Generator{
	private $level, $chunk, $random, $populators = [], $structure, $chunks, $options, $floorLevel, $preset;

	public function getSettings(){
		return [];
	}

	public function getName(){
		return "farmland";
	}

	public function __construct(array $option = []){}

	public function init(\pocketmine\level\ChunkManager $level, \pocketmine\utils\Random $random){
		$this->level = $level;
		$this->random = $random;
	}

	public function generateChunk($chunkX, $chunkZ){
		$chunk = clone $this->level->getChunk($chunkX, $chunkZ);
		$chunk->setGenerated();
		if($chunkX >= 0 && $chunkZ >= 0){
	 		for($x = 0, $realX = $chunkX * 16; $x < 16; $x++, $realX++){
				for($z = 0, $realZ = $chunkZ * 16; $z < 16; $z++, $realZ++){
					$farmX = $realX % 110;
					$farmZ = $realZ % 110;
					for($y = 0; $y < 3; $y++){
						$chunk->setBlockId($x, $y, $z, $y == 2 && ($farmX == 49 || $farmX == 50) && ($farmZ == 49 || $farmZ == 50) ? 7 : 95);
					}
					if($farmX < 100 && $farmZ < 100){
						if($farmX > 44 && $farmX <= 54 && $farmZ > 44 && $farmZ <= 54){
							$chunk->setBiomeColor($x, $z, 146, 188, 88);
							$chunk->setBlockId($x, 3, $z, 2);
						}elseif($farmX >= 44 && $farmX <= 55 && $farmZ >= 44 && $farmZ <= 55){
	//						$chunk->setBiomeColor($x, $z, 1, 1, 1);
							$chunk->setBiomeColor($x, $z, 58, 100, 0);
							for($y = 3; $y < 128; $y++){
								$chunk->setBlockId($x, $y, $z, $y % 2 == 0 ? 132 : 400);
							}
						}else{
	//						$chunk->setBiomeColor($x, $z, 1, 1, 1);
							$chunk->setBiomeColor($x, $z, 58, 100, 0);
							$chunk->setBlockId($x, 3, $z, 2);
						}
					}elseif($farmX == 100 || $farmX == 109 || $farmZ == 100 || $farmZ == 109){
						for($y = 3; $y < 128; $y++){
							$chunk->setBlockId($x, $y, $z, 95);
						}
					}
				}
			}
		}
		$this->level->setChunk($chunkX, $chunkZ, $chunk);
	}

	public function populateChunk($chunkX, $chunkZ){
	}

	public function getSpawn(){
		return new \pocketmine\math\Vector3(128, 125, 128);
	}
}