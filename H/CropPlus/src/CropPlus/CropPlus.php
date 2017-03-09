<?php

namespace CropPlus;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\level\Level;
use pocketmine\item\Item;
use pocketmine\block\Block;
use CropPlus\item\Dye;
use CropPlus\item\NetherWartSeeds;
use pocketmine\block\Grass;
use pocketmine\block\Mycelium;
use CropPlus\block\Farmland;
use pocketmine\block\Sapling;
use pocketmine\block\Leaves;
use pocketmine\block\Leaves2;
use CropPlus\block\Wheat;
use CropPlus\block\Beetroot;
use CropPlus\block\Carrot;
use CropPlus\block\Potato;
use pocketmine\block\Cactus;
use pocketmine\block\Sugarcane;
use CropPlus\block\MelonStem;
use CropPlus\block\PumpkinStem;
use CropPlus\block\CocoaBeans;
use CropPlus\block\NetherWart;

class CropPlus extends PluginBase implements Listener{ 
	const NETHER_WART_BLOCK = 115;
	const NETHER_WART_SEEDS = 372;
	const COCOA_BEANS_BLOCK = 127;

	public $randomTickBlocksProperty, $randomTickBlocks = [
// Dirts
		Block::GRASS => Grass::class,
		Block::MYCELIUM => Mycelium::class,
		Block::FARMLAND => Farmland::class,
// Crops
		Block::SAPLING => Sapling::class,
		Block::LEAVES => Leaves::class,
		Block::LEAVES2 => Leaves2::class,
		Block::WHEAT_BLOCK => Wheat::class,
		Block::BEETROOT_BLOCK => Beetroot::class,
		Block::CARROT_BLOCK => Carrot::class,
		Block::POTATO_BLOCK => Potato::class,
		Block::CACTUS => Cactus::class,
		Block::SUGARCANE_BLOCK => Sugarcane::class,
		Block::MELON_STEM => MelonStem::class,
		Block::PUMPKIN_STEM => PumpkinStem::class,
	 	self::COCOA_BEANS_BLOCK => CocoaBeans::class,
		self::NETHER_WART_BLOCK => NetherWart::class
	];

 	public function onLoad(){
 		$reflectionLevel = new \ReflectionClass(Level::class);
		$this->randomTickBlocksProperty = $reflectionLevel->getProperty("randomTickBlocks");
		$this->randomTickBlocksProperty->setAccessible(true);
		$this->registerBlock(Block::FARMLAND, Farmland::class);
		$this->registerBlock(Block::WHEAT_BLOCK, Wheat::class);
		$this->registerBlock(Block::BEETROOT_BLOCK, Beetroot::class);
		$this->registerBlock(Block::CARROT_BLOCK, Carrot::class);
		$this->registerBlock(Block::POTATO_BLOCK, Potato::class);
		$this->registerBlock(Block::MELON_STEM, MelonStem::class);
		$this->registerBlock(Block::PUMPKIN_STEM, PumpkinStem::class);
		$this->registerBlock(self::COCOA_BEANS_BLOCK, CocoaBeans::class);
		$this->registerBlock(self::NETHER_WART_BLOCK, NetherWart::class);
		$this->registerItem(Item::DYE, Dye::class);
		$this->registerItem(self::NETHER_WART_SEEDS, NetherWartSeeds::class);
 	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

 	public function onLevelLoad(LevelLoadEvent $event){
 		$this->randomTickBlocksProperty->setValue($event->getLevel(), $this->randomTickBlocks);
	}

	public function registerBlock($id, $class){
		Block::$list[$id] = $class;
		if($id < 255){
			Item::$list[$id] = $class;
			if(!Item::isCreativeItem($item = Item::get($id))){
				Item::addCreativeItem($item);
			}
		}
		for($damage = 0; $damage < 16; $damage){
			Block::$fullList[($id << 4) | $damage] = new $class($damage);
		}		
	}

	public function registerItem($id, $class){
		Item::$list[$id] = $class;
		if(!Item::isCreativeItem($item = new $class())){
			Item::addCreativeItem($item);
		}
	}
}