<?php

namespace MoreCraft;

use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\block\Planks as Wood;
use MoreCraft\items\SpruceWoodDoor as SpruceWoodDoorItem;
use MoreCraft\items\BirchWoodDoor as BirchWoodDoorItem;
use MoreCraft\items\JungleWoodDoor as JungleWoodDoorItem;
use MoreCraft\items\AcaciaWoodDoor as AcaciaWoodDoorItem;
use MoreCraft\items\DarkOakWoodDoor as DarkOakWoodDoorItem;
use MoreCraft\blocks\SpruceWoodDoor as SpruceWoodDoorBlock;
use MoreCraft\blocks\BirchWoodDoor as BirchWoodDoorBlock;
use MoreCraft\blocks\JungleWoodDoor as JungleWoodDoorBlock;
use MoreCraft\blocks\AcaciaWoodDoor as AcaciaWoodDoorBlock;
use MoreCraft\blocks\DarkOakWoodDoor as DarkOakWoodDoorBlock;

class Doors extends \pocketmine\plugin\PluginBase{
	const SPRUCE_WOODEN_DOOR_ITEM = 427;
	const SPRUCE_WOODEN_DOOR_BLOCK = 193;
	const BIRCH_WOODEN_DOOR_ITEM = 428;
	const BIRCH_WOODEN_DOOR_BLOCK = 194;
	const JUNGLE_WOODEN_DOOR_ITEM = 429;
	const JUNGLE_WOODEN_DOOR_BLOCK = 195;
	const ACACIA_WOODEN_DOOR_ITEM = 430;
	const ACACIA_WOODEN_DOOR_BLOCK = 196;
	const DARK_OAK_WOODEN_DOOR_ITEM = 431;
	const DARK_OAK_WOODEN_DOOR_BLOCK = 197;

 	public function onLoad(){
		Block::$list[self::SPRUCE_WOODEN_DOOR_BLOCK] = SpruceWoodDoorBlock::class;
		Block::$list[self::BIRCH_WOODEN_DOOR_BLOCK] = BirchWoodDoorBlock::class;
		Block::$list[self::JUNGLE_WOODEN_DOOR_BLOCK] = JungleWoodDoorBlock::class;
		Block::$list[self::ACACIA_WOODEN_DOOR_BLOCK] = AcaciaWoodDoorBlock::class;
		Block::$list[self::DARK_OAK_WOODEN_DOOR_BLOCK] = DarkOakWoodDoorBlock::class;
		for($data = 0; $data < 16; ++$data){
			Block::$fullList[(self::SPRUCE_WOODEN_DOOR_BLOCK << 4) | $data] = new SpruceWoodDoorBlock($data);
			Block::$fullList[(self::BIRCH_WOODEN_DOOR_BLOCK << 4) | $data] = new BirchWoodDoorBlock($data);
			Block::$fullList[(self::JUNGLE_WOODEN_DOOR_BLOCK << 4) | $data] = new JungleWoodDoorBlock($data);
			Block::$fullList[(self::ACACIA_WOODEN_DOOR_BLOCK << 4) | $data] = new AcaciaWoodDoorBlock($data);
			Block::$fullList[(self::DARK_OAK_WOODEN_DOOR_BLOCK << 4) | $data] = new DarkOakWoodDoorBlock($data);
		}
		Item::$list[self::SPRUCE_WOODEN_DOOR_ITEM] = SpruceWoodDoorItem::class;
		Item::$list[self::BIRCH_WOODEN_DOOR_ITEM] = BirchWoodDoorItem::class;
		Item::$list[self::JUNGLE_WOODEN_DOOR_ITEM] = JungleWoodDoorItem::class;
		Item::$list[self::ACACIA_WOODEN_DOOR_ITEM] = AcaciaWoodDoorItem::class;
		Item::$list[self::DARK_OAK_WOODEN_DOOR_ITEM] = DarkOakWoodDoorItem::class;
		foreach([
			self::SPRUCE_WOODEN_DOOR_ITEM, 
			self::BIRCH_WOODEN_DOOR_ITEM, 
			self::JUNGLE_WOODEN_DOOR_ITEM, 
			self::ACACIA_WOODEN_DOOR_ITEM, 
			self::DARK_OAK_WOODEN_DOOR_ITEM
		] as $id){
			if(!Item::isCreativeItem($item = Item::get($id))){
				Item::addCreativeItem($item);
			}
		}
/*
		foreach($this->getServer()->getCraftingManager()->getRecipes() as $recipe){
			if($recipe->getResult()->getID() == Item::WOODEN_DOOR){
				for($x = 0; $x < 3; ++$x){
					for($y = 0; $y < 4; ++$y){
						$recipe->setIngredient(($y * 3) + $x, Item::get(Item::PLANKS, WOOD::OAK, 1));
					}
				}
			}
		}
*/
 		foreach([
// 			WOOD::OAK => Item::WOODEN_DOOR,
 			WOOD::SPRUCE => self::SPRUCE_WOODEN_DOOR_ITEM,
 			WOOD::BIRCH => self::BIRCH_WOODEN_DOOR_ITEM,
 			WOOD::JUNGLE => self::JUNGLE_WOODEN_DOOR_ITEM,
 			WOOD::ACACIA => self::ACACIA_WOODEN_DOOR_ITEM,
 			WOOD::DARK_OAK => self::DARK_OAK_WOODEN_DOOR_ITEM
 		] as $type => $door){
			$this->getServer()->getCraftingManager()->registerRecipe(
				(new \pocketmine\inventory\BigShapedRecipe(Item::get($door, 0, 1),
					"PP",
					"PP",
					"PP"
				))->setIngredient("P", Item::get(Item::PLANKS, $type, 1))
			);
		}
 	}
}