<?php

namespace MoreCraft;

use pocketmine\item\Item;

class WaterBottle extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	const GLASS_BOTTLE = 374;
	const WATER_BOTTLE = 373;
	const GHAST_TEAR = 370;

 	public function onLoad(){
		foreach([self::GLASS_BOTTLE, self::WATER_BOTTLE, self::GHAST_TEAR] as $id){
			if(!Item::isCreativeItem($item = new Item($id))){
				Item::addCreativeItem($item);
			}
		}
 		Item::$list[self::WATER_BOTTLE] = WaterBottleItem::class;
		$this->getServer()->getCraftingManager()->registerRecipe(
			(new \pocketmine\inventory\BigShapedRecipe(Item::get(self::GLASS_BOTTLE, 0, 3),
				"   ",
				"G G",
				" G "
			))->setIngredient("G", Item::get(Item::GLASS, 0, 1))
		);
		$this->getServer()->getCraftingManager()->registerRecipe(
			(new \pocketmine\inventory\BigShapedRecipe(Item::get(self::WATER_BOTTLE, 0, 1),
				"   ",
				"BWB",
				"TBT"
			))->setIngredient("W", Item::get(Item::BUCKET, 8, 1))->setIngredient("T", Item::get(self::GHAST_TEAR, 0, 3))->setIngredient("B", Item::get(self::GLASS_BOTTLE, 0, 1))
		);
		$this->windowIndexProperty = (new \ReflectionClass("\\pocketmine\\Player"))->getProperty("windowIndex");
		$this->windowIndexProperty->setAccessible(true);
 	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlayerBucketFill(\pocketmine\event\player\PlayerBucketFillEvent $event){
		$item = $event->getItem();
		if($event->getPlayer()->isSurvival() && $item->getID() == Item::BUCKET && $item->getDamage() == 0){
			$event->setCancelled();
		}
	}

	public function onPacket(\pocketmine\event\server\DataPacketReceiveEvent $event){
		$player = $event->getPlayer();
		$packet = $event->getPacket();
		if($packet->pid() == \pocketmine\network\protocol\Info::CRAFTING_EVENT_PACKET &&	$packet->output[0]->getID() == self::WATER_BOTTLE && $player->spawned !== \false && $player->isAlive()){
			$windowIndex = $this->windowIndexProperty->getValue($player);
			$inventory = $player->getInventory();
			if($this->getServer()->getCraftingManager()->getRecipe($packet->id) == null){
				$inventory->sendContents($player);
			}else{
				$ingredients = $packet->input;
				foreach($ingredients as $index => $need){
 					foreach($inventory->getContents() as $item){
						if($need->getID() == 0 || $item->getCount() > 0 && $need->getID() == $item->getID()){
							unset($ingredients[$index]);							
						}
					}
				}
				if(empty($ingredients)){
					$inventory->addItem(Item::get(Item::BUCKET, 0, 1));
				}
			}
		}
	}
}