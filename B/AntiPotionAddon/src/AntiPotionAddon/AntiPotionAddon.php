<?php

namespace AntiPotionAddon;

use pocketmine\network\protocol\MobEffectPacket;
use pocketmine\entity\Effect;

class AntiPotionAddon extends \pocketmine\plugin\PluginBase{
	public function onLoad(){
		$effect = new MobEffectPacket();
		$effect01 = clone $effect;
		$effect02 = clone $effect;
 		$effect01->eventId = MobEffectPacket::EVENT_MODIFY;
		$effect02->eventId = MobEffectPacket::EVENT_REMOVE;
		$this->efects = [];
		foreach([1, 2, 3, 4, 5, 8, 9, 10, 11, 12, 13, 14, 18, 19, 20, 21] as $id){
			$effect1 = clone $effect01;
			$effect2 = clone $effect02;
			$effect1->effectId = $effect2->effectId = $id;
 			$this->effects[$id] = [MobEffectPacket::EVENT_MODIFY => $effect1, MobEffectPacket::EVENT_REMOVE => $effect2];			
		}
	}

	public function onEnable(){
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this,"onTick"]), 3);
	}
	
	public function onTick(){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$effects = $player->getEffects();
			foreach($this->effects as $id => $packets){
				if(isset($effects[$id])){
					$packets[MobEffectPacket::EVENT_MODIFY]->eid = $player->getId();
					$packets[MobEffectPacket::EVENT_MODIFY]->particles = $effects[$id]->isVisible();
					$packets[MobEffectPacket::EVENT_MODIFY]->amplifier = $effects[$id]->getAmplifier();
					$packets[MobEffectPacket::EVENT_MODIFY]->duration = $effects[$id]->getDuration();
					$player->dataPacket($packets[MobEffectPacket::EVENT_MODIFY]);					
				}else{
					$packets[MobEffectPacket::EVENT_REMOVE]->eid = $player->getId();
					$player->dataPacket($packets[MobEffectPacket::EVENT_REMOVE]);							
				}
			}
		}
	}
}