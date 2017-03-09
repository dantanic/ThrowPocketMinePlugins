<?php

namespace FakeServer; 

use pocketmine\Player;
use pocketmine\utils\TextFormat as Color;

class AntiBlockLauncher extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onLoad(){
		$this->players = [];
		$fakeNetwork = new FakeNetwork($server = $this->getServer());
		$fakeNetwork->setName($server->getMotd());
		$fakeNetwork->registerInterface(new FakeRakLibInterface($server, $this));
		$networkProperty = new \ReflectionClass("\\pocketMine\\Server")->getProperty("network");
		$networkProperty->setAccessible(true);
		$networkProperty->setValue($server, $fakeNetwork);
	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function isNewMCPE(Player $player){
		if($player->loggedIn){
			return isset($this->players[$player->getName()]);
		}
	}

	public function setNewMCPE(Player $player){
		if($player->loggedIn){
			$this->players[$player->getName()] = $player;		
		}
	}

	public function onDisable(){
		foreach($this->players as $player){
			if($player->loggedI{
				$player->close("", Color::RED . Color::BOLD . "FakeServer is disabled...");
			}
		}
	}

	public function onPlayerQuit(\pocketmine\event\player\PlayerQuitEvent $event){
		unset($this->players[$event->getPlayer()->getName()]);
	}
}