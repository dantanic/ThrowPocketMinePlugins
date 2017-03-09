<?php

namespace MoreCraft;

use pocketmine\item\Item;

class Seed2BoneMeal extends \pocketmine\plugin\PluginBase{
 	public function onLoad(){
 		$this->getServer()->getCraftingManager()->registerRecipe(
			(new \pocketmine\inventory\ShapedRecipe(Item::get(Item::DYE, 15, 1),
				"S ",
				"  "
			))->setIngredient("S", Item::get(Item::SEEDS, 0, 1))
		);
 		$this->getServer()->getCraftingManager()->registerRecipe(
			(new \pocketmine\inventory\BigShapedRecipe(Item::get(Item::DYE, 15, 9),
				"SSS",
				"SSS",
				"SSS"
			))->setIngredient("S", Item::get(Item::SEEDS, 0, 1))
		);
 	}
}