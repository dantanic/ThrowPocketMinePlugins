<?php

namespace MoreGenerator;

class MainIslandGenerator extends \pocketmine\level\generator\Generator{
	private $level, $chunk, $random, $populators = [], $structure, $chunks, $options, $floorLevel, $preset;

	public function getSettings(){
		return [];
	}

	public function getName(){
		return "mainisland";
	}

	public function __construct(array $option = []){}

	public function init(\pocketmine\level\ChunkManager $level, \pocketmine\utils\Random $random){
		$this->level = $level;
		$this->random = $random;
	}

	public function generateChunk($chunkX, $chunkZ){
		$chunk = clone $this->level->getChunk($chunkX, $chunkZ);
		$chunk->setGenerated();
 		for($x = 0, $realX = $chunkX * 16; $x < 16; $x++, $realX++){
			for($z = 0, $realZ = $chunkZ * 16; $z < 16; $z++, $realZ++){
				$chunk->setBiomeColor($x, $z, 146, 188, 88);
				if($realX < 178 && $realX > -78 && $realZ < 178 && $realZ > -78){
					$chunk->setBlockId($x, 0, $z, 7);					
					$chunk->setBlockId($x, 1, $z, 3);					
					$chunk->setBlockId($x, 2, $z, 3);					
					$chunk->setBlockId($x, 3, $z, 3);					
					$chunk->setBlockId($x, 4, $z, 3);					
					$chunk->setBlockId($x, 5, $z, 2);					
				}
			}
		}
		$this->level->setChunk($chunkX, $chunkZ, $chunk);
	}

	public function populateChunk($chunkX, $chunkZ){
		$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed());
		foreach($this->populators as $populator){
			$populator->populate($this->level, $chunkX, $chunkZ, $this->random);
		}
		$chunk = $this->level->getChunk($chunkX, $chunkZ);
		$biome = \pocketmine\level\generator\biome\Biome::getBiome($chunk->getBiomeId(7, 7));
		$biome->populateChunk($this->level, $chunkX, $chunkZ, $this->random);
	}

	public function getSpawn(){
		return new \pocketmine\math\Vector3(0, 7, 0);
	}
}