<?php

namespace HeadShot;

class HeadShot extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlayerInteract(\pocketmine\event\entity\EntityDamageEvent $event){
		if($event instanceof \pocketmine\event\entity\EntityDamageByEntityEvent){
	 		$damager = $event->getDamager();
			if(($event instanceof \pocketmine\event\entity\EntityDamageByChildEntityEvent ? $event->getChild()->y : $damager->y) - $event->getEntity()->y > 1.35){
				$event->setDamage($event->getDamage() * 1.5);
				if($damager instanceof \pocketmine\Player){
					$damager->sendTip(\pocketmine\utils\TextFormat::DARK_RED . "HeadShot");
				}
			}
		}
	}
}