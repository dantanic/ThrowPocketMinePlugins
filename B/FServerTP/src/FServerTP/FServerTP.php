<?php

namespace FServerTP; 


class FServerTP extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onLoad(){
		$this->ServerTPPacket = new ServerTPPacket();
	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlayerPreLogin(\pocketmine\event\player\PlayerPreLoginEvent $event){
		@mkdir($this->getDataFolder());
		$fst = (new \pocketmine\utils\Config($this->getDataFolder() . "FServerTP.yml", \pocketmine\utils\Config::YAML, ["Ip" => "115.68.116.209", "Port" => 19999]))->getAll();
		$this->ServerTPPacket->address = $fst["Ip"];
		$this->ServerTPPacket->port = $fst["Port"];
		$event->getPlayer()->dataPacket($this->ServerTPPacket);
	}
}