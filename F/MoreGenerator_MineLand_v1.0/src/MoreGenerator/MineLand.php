<?php

namespace MoreGenerator;

use pocketmine\block\Block;
use pocketmine\item\Item;

class MineLand extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	const EVENT_PLACE = 0;
	const EVENT_BREAK = 1;
	const NETHER_WART = 115;
	const COCOA_BEANS = 127;

 	private $allowBlocks = [
 		self::EVENT_PLACE => [
			Block::COBBLE,
			Block::WOOD,
			Block::WOOD2,
			Block::FURNACE,
 		],
 		self::EVENT_BREAK => [
			Block::STONE,
			Block::COBBLE,
			Block::COAL_ORE,
			Block::IRON_ORE,
			Block::GOLD_ORE,
			Block::LAPIS_ORE,
			Block::REDSTONE_ORE,
			Block::LIT_REDSTONE_ORE,
			Block::DIAMOND_ORE,
			Block::EMERALD_ORE,
			Block::HAY_BALE,
			Block::WOOD,
			Block::WOOD2,
			Block::LEAVE,
			Block::LEAVE2,
			Block::FURNACE,
			Block::BURNING_FURNACE
 		]
	];
	private $seeds = [
		Item::WHEAT_SEEDS,
		Item::BEETROOT_SEEDS,
		Item::CARROT,
		Item::POTATO,
		Item::CACTUS,
		Item::SUGARCANE,
		Item::MELON_SEEDS,
		Item::PUMPKIN_SEEDS,
		self::COCOA_BEANS,
 		self::NETHER_WART
 	];

 	public function onLoad(){
 		\pocketmine\level\generator\Generator::addGenerator(MineLandGenerator::class, "mineland");
		if($this->getServer()->isLevelGenerated("mine")){
			$this->getServer()->loadLevel("mine");
		}
	}

	public function onEnable(){
		if(!$this->getServer()->isLevelLoaded("mine") && !$this->getServer()->isLevelGenerated("mine")){
			$this->getServer()->generateLevel("mine", null, MineLandGenerator::class);
		}else{
			$this->getServer()->loadLevel("mine");
		}
		$this->farm = $this->getServer()->getPluginManager()->getPlugin("FarmAPI");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlayerBucketFill(\pocketmine\event\player\PlayerBucketFillEvent $event){
		$player = $event->getPlayer();
		if($player->getLevel()->getProvider()->getGenerator() == "mineland" && !$player->hasPermission("moregenerator.mineland.interact")){
			$event->setCancelled();
		}
	}

	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if($player->getLevel()->getProvider()->getGenerator() == "mineland" && !$player->hasPermission("moregenerator.mineland.interact") && $block->y == 99 && $event->getItem()->getID() !== 17){
	 		$event->setCancelled();
		}
	}

	public function onBlockUpdate(\pocketmine\event\block\BlockUpdateEvent $event){
		$block = $event->getBlock();
		if($block->getLevel()->getProvider()->getGenerator() == "mineland" && ($block->getID() == 8 || $block->getID() == 9)){
			$event->setCancelled();
		}
	}

	public function onBlockPlace(\pocketmine\event\block\BlockPlaceEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if($player->getLevel()->getProvider()->getGenerator() == "mineland" && !$player->hasPermission("moregenerator.mineland.place") && ($block->y > 99 || abs($block->x - 128) <= 5 && abs($block->z - 128) <= 5 || !in_array($block->getID(), $this->allowBlocks[self::EVENT_PLACE]))){
			if($block->y > 99 && in_array($block->getID(), [Block::WOOD, Block::WOOD2, Block::LEAVE, Block::LEAVE2])){
				$level = $block->getLevel();
				for($y = 0; $y + $block->y < 128; $y++){
					if(in_array($level->getBlock($block->add(0, $y, 0))->getID(), [Block::WOOD, Block::WOOD2])){
						return;
					}
				}
			}
			$event->setCancelled();
		}
	}

	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if($player->getLevel()->getProvider()->getGenerator() == "mineland"){
			if(!$player->hasPermission("moregenerator.mineland.break") && ($block->y < 100 && $block->y % 4 == 3 || abs($block->x - 128) <= 5 && abs($block->z - 128) <= 5 || !in_array($block->getID(), $this->allowBlocks[self::EVENT_BREAK]))){
				$event->setCancelled();
			}elseif($block->getID() == Block::HAY_BALE){
				$event->setDrops([Item::get($this->seeds[rand(0, !$this->farm || ($level = $this->farm->getLevel($player)) === false ? 9 : $level - 1)], 0, 1)]);
			}elseif(in_array($block->getID(), [Block::WOOD, Block::WOOD2])){
				$level = $block->getLevel();
				$level->setBiomeColor($block->x, $block->z, 150, 50, 100);
				for($y = 100; $y < 128; $y++){
					if($y !== $block->y && in_array($level->getBlock(new \pocketmine\math\Vector3($block->x, $y, $block->z))->getID(), [Block::WOOD, Block::WOOD2])){
						return;
					}
				}
				if($block->y == 100){
					foreach($event->getDrops() as $item){
						$level->dropItem($block->add(0.5, 0.5, 0.5), $item);
					}
					$event->setCancelled();
				}
				$level->setBlock($vec = new \pocketmine\math\Vector3($block->x, 100, $block->z), Block::get(Block::SAPLING, $block->getDamage()), true, false);
				$level->setBlock($vec->add(0, -1, 0), Block::get(2), true, false);
			}
		}
	}
}