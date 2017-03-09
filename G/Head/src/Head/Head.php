<?php

namespace Head;

class Head extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->skins = [];
	}

	public function onDataPacketReceive(\pocketmine\event\server\DataPacketReceiveEvent $event){
		$packet = $event->getPacket();
		if($packet->pid() == 0x82) $this->skins[strtolower(\pocketmine\utils\TextFormat::clean($packet->username))] = $packet->skin;
 	}
 
 public function onPlayerJoin(){
 	$player = $event->getPlayer();
 	if(isset($this->skins[$name = strtolower($player->getName())])) $player->setSkin($this->skins[$name]);
 }

	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$level = $player->getLevel();
		$skin = substr($player->getSkinData(), ($pos = (64 * 8 * 4)) - 4, $pos);
		for($x = 0; $x < 8; $x++){
			for($y = 0; $y < 8; $y++){
				$key = ((64 * $y) + 8 + $x) * 4;
				$level->addParticle(new \pocketmine\level\particle\DustParticle($player->add($xx = ($x - 4) * 0.3, $yy = 4 - ($y * 0.4), 0), $r = ord($skin{$key}), $g = ord($skin{$key+1}), $b = ord($skin{$key+2}), $a = ord($skin{$key+3})));
				$level->addParticle(new \pocketmine\level\particle\DustParticle($player->add($xx, $yy + ($y * 0.15), 0), $r, $g, $b, $a));
				$level->addParticle(new \pocketmine\level\particle\DustParticle($player->add($xx + ($x - 4) * 0.15, $yy + ($y * 0.2), 0), $r, $g, $b, $a));
				$level->addParticle(new \pocketmine\level\particle\DustParticle($player->add($xx + ($x - 4) * 0.15, $yy, 0), $r, $g, $b, $a));
			}	
		}
	}
}