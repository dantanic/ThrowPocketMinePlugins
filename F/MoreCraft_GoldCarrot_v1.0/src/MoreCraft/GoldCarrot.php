<?php

namespace MoreCraft;

use pocketmine\item\Item;

class GoldCarrot extends \pocketmine\plugin\PluginBase{
	const GOLD_CARROT = 396;

 	public function onLoad(){
		foreach([self::GOLD_CARROT, Item::GOLD_NUGGET] as $id){
			if(!Item::isCreativeItem($item = new Item($id))){
				Item::addCreativeItem($item);
			}
		}
		$this->getServer()->getCraftingManager()->registerRecipe(
			(new \pocketmine\inventory\BigShapedRecipe(Item::get(Item::GOLD_NUGGET, 0, 9),
				"   ",
				" G ",
				"   "
			))->setIngredient("G", Item::get(Item::GOLD_INGOT, 0, 1))
		);
		$this->getServer()->getCraftingManager()->registerRecipe(
			(new \pocketmine\inventory\BigShapedRecipe(Item::get(Item::GOLD_INGOT, 0, 1),
				"GGG",
				"GGG",
				"GGG"
			))->setIngredient("G", Item::get(Item::GOLD_NUGGET, 0, 1))
		);
		$this->getServer()->getCraftingManager()->registerRecipe(
			(new \pocketmine\inventory\BigShapedRecipe(Item::get(self::GOLD_CARROT, 0, 1),
				"GGG",
				"GCG",
				"GGG"
			))->setIngredient("G", Item::get(Item::GOLD_NUGGET, 0, 1))
				->setIngredient("C", Item::get(Item::CARROT, 0, 1))
		);
 	}
}