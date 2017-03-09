<?php

namespace RidePlayer;

class RidePlayer extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onLoad(){
		$this->SetEntityLinkPacket = new \pocketmine\network\protocol\SetEntityLinkPacket();
 	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDataPacketReceive(\pocketmine\event\server\DataPacketReceiveEvent $event){
		$packet = $event->getPacket();
		if($packet->pid() == 0x9a){
			$player = $event->getPlayer();
			if($packet->action == 3){
				foreach($player->getViewers() as $viewer){
 					$this->SetEntityLinkPacket->type = 0;
					$this->SetEntityLinkPacket->from = $this->SetEntityLinkPacket->to = $player->getID();
					$viewer->dataPacket($this->SetEntityLinkPacket);
				}
			}else{
 				if($player->isOp() && $player->getInventory() instanceof \pocketmine\inventory\PlayerInventory && $player->getInventory()->getItemInHand()->getID() == 280 && ($target = $player->getLevel()->getEntity($packet->target)) instanceof \pocketmine\Player){
					$this->SetEntityLinkPacket->type = 1;
					$this->SetEntityLinkPacket->from = $target->getID();
					$this->SetEntityLinkPacket->to = $player->getID();
					foreach($player->getViewers() as $viewer){
						if($viewer === $player){
							$pk = clone $this->SetEntityLinkPacket;
							$pk->to = 0;
							$player->dataPacket($pk);
						}elseif($viewer === $target){
							$pk = clone $this->SetEntityLinkPacket;
							$pk->from = 0;
							$target->dataPacket($pk);
						}else{
							$viewer->dataPacket($this->SetEntityLinkPacket);
 						}
					}
				}
			}
		}
	}
}