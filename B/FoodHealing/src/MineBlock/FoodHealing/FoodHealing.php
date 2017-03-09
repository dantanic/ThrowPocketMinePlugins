<?php

namespace MineBlock\FoodHealing;

use pocketmine\Player;
use pocketmine\entity\Effect;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\network\Network;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class FoodHealing extends PluginBase implements Listener{

	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->foodTable = (new Config($this->getDataFolder() . "FoodHealing.yml", Config::YAML, [Item::APPLE => 4, Item::MUSHROOM_STEW => 10, Item::BEETROOT_SOUP => 10, Item::BREAD => 5, Item::RAW_PORKCHOP => 3, Item::COOKED_PORKCHOP => 8, Item::RAW_BEEF => 3, Item::STEAK => 8, Item::COOKED_CHICKEN => 6, Item::RAW_CHICKEN => 2, Item::MELON_SLICE => 2, Item::GOLDEN_APPLE => 10, Item::PUMPKIN_PIE => 8, Item::CARROT => 4, Item::POTATO => 1, Item::BAKED_POTATO => 6,	Item::COOKIE => 2, Item::COOKED_FISH => [5, 6], Item::RAW_FISH => [1, 2, 1, 1]]))->getAll();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDataPacketReceive(DataPacketReceiveEvent $event){
		$pk = $event->getPacket();
		$p = $event->getPlayer();
		if($pk->pid() == ProtocolInfo::ENTITY_EVENT_PACKET && $pk->event == 9){
			$i = $p->getInventory()->getItemInHand();
			if($p->getHealth() < $p->getMaxHealth() && isset($this->foodTable[$i->getID()])){
				$event->setCancelled();
				$this->getServer()->getPluginManager()->callEvent($ev = new PlayerItemConsumeEvent($p, $i));
				if($ev->isCancelled()){
					$p->getInventory()->sendContents($p);
					return;
				}
				$pk = new EntityEventPacket();
				$pk->eid = 0;
				$pk->event = 9;
				$pk->setChannel(Network::CHANNEL_WORLD_EVENTS);
 				$p->dataPacket($pk);
				$pk->eid = $p->getId();
				$this->getServer()->broadcastPacket($p->getViewers(), $pk);
				$amount = $this->foodTable[$i->getID()];
				if(is_array($amount)) $amount = isset($amount[$i->getDamage()]) ? $amount[$i->getDamage()] : 0;
      $ev = new EntityRegainHealthEvent($p, $amount, EntityRegainHealthEvent::CAUSE_EATING);
				$p->heal($ev->getAmount(), $ev);
				--$i->count;
				$p->getInventory()->setItemInHand($i, $p);
				if($i->getID() === Item::MUSHROOM_STEW or $i->getID() === Item::BEETROOT_SOUP) $p->getInventory()->addItem(Item::get(Item::BOWL, 0, 1));
				elseif($i->getID() === Item::RAW_FISH and $i->getDamage() === 3){
					//$p->addEffect(Effect::getEffect(Effect::HUNGER)->setAmplifier(2)->setDuration(15 * 20));
					$p->addEffect(Effect::getEffect(Effect::NAUSEA)->setAmplifier(1)->setDuration(15 * 20));
					$p->addEffect(Effect::getEffect(Effect::POISON)->setAmplifier(3)->setDuration(60 * 20));
				}
			}
		}
	}
}