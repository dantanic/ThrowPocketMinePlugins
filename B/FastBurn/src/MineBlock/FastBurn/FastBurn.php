<?php

namespace MineBlock\FastBurn;

use pocketmine\event\Listener;
use pocketmine\event\inventory\FurnaceBurnEvent;
use pocketmine\event\inventory\FurnaceSmeltEvent;
use pocketmine\plugin\PluginBase;

class FastBurn extends PluginBase implements Listener{
	public function onEnable(){
		$this->furnace = [];
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onFuranceBurn(FurnaceBurnEvent $event){
		$this->getServer()->getScheduler()->scheduleDelayedTask(new Task($this, [$this,"runSmelt"], [$event->getFurnace()]), 2);
	}

	public function onFuranceSmelt(FurnaceSmeltEvent $event){
		$this->getServer()->getScheduler()->scheduleDelayedTask(new Task($this, [$this,"runSmelt"], [$event->getFurnace()]), 2);
 	}

	public function runSmelt($furnace){
		$inv = $furnace->getInventory();
		$fuel = $inv->getFuel();
		$raw = $inv->getSmelting();
		$product = $inv->getResult();
		$smelt = $this->getServer()->getCraftingManager()->matchFurnaceRecipe($raw);
		if($smelt !== null && $raw->getCount() > 0 && (($smelt->getResult()->equals($product, true) && $product->getCount() < $product->getMaxStackSize()) || $product->getId() === 0)){
			$furnace->namedtag["BurnTime"] -= 200;
			$furnace->namedtag["CookTime"] = 200;
		}else{
			$furnace->namedtag["BurnTime"] = $furnace->namedtag["CookTime"] = 0;
		}
	}
}