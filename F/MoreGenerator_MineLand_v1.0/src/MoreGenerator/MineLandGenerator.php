<?php

namespace MoreGenerator;

use pocketmine\block\Block;

class MineLandGenerator extends \pocketmine\level\generator\Generator{
	private $level, $chunk, $random, $populators = [], $structure, $chunks, $options, $floorLevel, $preset, $ores = [];

	public function getSettings(){
		return [];
	}

	public function getName(){
		return "mineland";
	}

	public function __construct(array $option = []){}

	public function init(\pocketmine\level\ChunkManager $level, \pocketmine\utils\Random $random){
		$this->level = $level;
		$this->random = $random;
		for($i = 0; $i <= 5; $i++){
			$tree = new \pocketmine\level\generator\populator\Tree($i);
			$tree->setBaseAmount(1);
			$this->populators[] = $tree;
		}
		foreach([
			[Block::STONE, 10000],
			[Block::COAL_ORE, 100],
			[Block::IRON_ORE, 40],
			[Block::GOLD_ORE, 20],
			[Block::LAPIS_ORE, 20],
			[Block::REDSTONE_ORE, 30],
			[Block::DIAMOND_ORE, 3],
			[Block::EMERALD_ORE, 1],
			[Block::HAY_BALE, 5]
		] as $data){
			for($i = 0; $i < $data[1]; $i++){
				$this->ores[] = $data[0];
			}
		}
	}

	public function generateChunk($chunkX, $chunkZ){
		$chunk = clone $this->level->getChunk($chunkX, $chunkZ);
		$chunk->setGenerated();
 		for($x = 0, $realX = $chunkX * 16; $x < 16; $x++, $realX++){
			for($z = 0, $realZ = $chunkZ * 16; $z < 16; $z++, $realZ++){
//				$chunk->setBiomeColor($x, $z, 146, 188, 88);
				$chunk->setBiomeColor($x, $z, 255, 120, 180);
// R: 255  G: 100  B: 200
 				for($y = 0; $y < 100; $y++){
					if($y < 2){
						$chunk->setBlockId($x, $y, $z, 7);
					}elseif(($disX = abs($realX - 128)) <= 5 && ($disZ = abs($realZ - 128)) <= 5){
						if($disX <= 1 && $disZ <= 1){
							if($y % 2 == 1){
								$chunk->setBlockId($x, $y, $z, 8);
							}
						}elseif($disX == 2 && $disZ == 2){
							if($y % 4 == 3){
								$chunk->setBlockId($x, $y, $z, 124);
							}
						}elseif($y % 4 === 3 || $y == 2){
							$chunk->setBlockId($x, $y, $z, 5);
						}
					}else{
						if($y == 99){
							$chunk->setBlockId($x, $y, $z, 2);
						}elseif($y % 4 === 3){
							if($x % 4 == 0 && $z % 4 == 0){
								$chunk->setBlockId($x, $y, $z, 124);
							}elseif($x % 4 == 2 && $z % 4 == 1){
								$chunk->setBlockId($x, $y, $z, 58);
							}else{
								$chunk->setBlockId($x, $y, $z, 5);
							}
						}elseif(abs($realX - 128) <= 6 && abs($realZ - 128) <= 6){
							$chunk->setBlockId($x, $y, $z, 1);
						}else{
							$chunk->setBlockId($x, $y, $z, $this->ores[array_rand($this->ores)]);
						}
					}
				}
			}
		}
		foreach($this->populators as $populator){
			$populator->populate($this->level, $chunkX, $chunkZ, $this->random);
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
		return new \pocketmine\math\Vector3(128, 125, 128);
	}
}