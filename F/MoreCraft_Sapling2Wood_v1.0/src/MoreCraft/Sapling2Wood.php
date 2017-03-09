<?php

namespace MoreCraft;

use pocketmine\item\Item;

class Sapling2Wood extends \pocketmine\plugin\PluginBase{
 	public function onLoad(){
 		for($meta = 0; $meta < 6; $meta++){
 			$this->getServer()->getCraftingManager()->registerRecipe(
				(new \pocketmine\inventory\BigShapedRecipe(Item::get(Item::PLANK, $meta, 1),
					"SSS",
					"SSS",
					"SSS"
				))->setIngredient("S", Item::get(Item::SAPLING, $meta, 1))
			);
  		}
 	}
}