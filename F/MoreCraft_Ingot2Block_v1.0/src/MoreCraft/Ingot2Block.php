<?php

namespace MoreCraft;

use pocketmine\item\Item;

class Ingot2Block extends \pocketmine\plugin\PluginBase{
 	public function onLoad(){
 		foreach([
 			Item::COAL_BLOCK => Item::COAL,
 			Item::IRON_BLOCK => Item::IRON_INGOT,
 			Item::GOLD_BLOCK => Item::GOLD_INGOT,
 			Item::REDSTONE_BLOCK => Item::REDSTONE,
 			Item::LAPIS_BLOCK => [Item::DYE, 4],
 			Item::DIAMOND_BLOCK => Item::DIAMOND,
 			Item::EMERALD_BLOCK => Item::EMERALD
 		] as $block => $ingot){
			if(!is_array($ingot)){
 				$ingot = [$ingot, 0];
 			}
 			$ingot[2] = 1;
 			$ingot[2] = 1;
			$this->getServer()->getCraftingManager()->registerRecipe(
				(new \pocketmine\inventory\BigShapedRecipe(Item::get($block),
					"III",
					"III",
					"III"
				))->setIngredient("I", Item::get(...$ingot))
			);
 			$ingot[2] = 9;
			$this->getServer()->getCraftingManager()->registerRecipe(
				(new \pocketmine\inventory\ShapedRecipe(Item::get(...$ingot),
					"B"
				))->setIngredient("B", Item::get($block))
			);
 		}
 	}
}