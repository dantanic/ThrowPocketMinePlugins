<?php

namespace MoreCraft;

use pocketmine\item\Item;

class GoldApple extends \pocketmine\plugin\PluginBase{
 	public function onLoad(){
		if(!Item::isCreativeItem($item = new Item(Item::GOLDEN_APPLE, 0))){
			Item::addCreativeItem($item);
		}
		if(!Item::isCreativeItem($item = new Item(Item::GOLDEN_APPLE, 1))){
			Item::addCreativeItem($item);
		}
		$this->getServer()->getCraftingManager()->registerRecipe(
			(new \pocketmine\inventory\BigShapedRecipe(Item::get(Item::GOLDEN_APPLE, 0, 1),
				"GGG",
				"GAG",
				"GGG"
			))->setIngredient("G", Item::get(Item::GOLD_INGOT, 0, 1))
				->setIngredient("A", Item::get(Item::APPLE, 0, 1)) 
		);
		$this->getServer()->getCraftingManager()->registerRecipe(
			(new \pocketmine\inventory\BigShapedRecipe(Item::get(Item::GOLDEN_APPLE, 1, 1),
				"GGG",
				"GAG",
				"GGG"
			))->setIngredient("G", Item::get(Item::GOLD_BLOCK, 0, 1))
				->setIngredient("A", Item::get(Item::APPLE, 0, 1)) 
		);
 	}
}