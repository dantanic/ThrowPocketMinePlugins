<?php

namespace MineBlock\ServerName;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\network\protocol\Info;
use pocketmine\utils\TextFormat as Color;

class ServerName extends PluginBase{
	public $reflect = null;
	
	public function onEnable(){
		if($this->reflect == null){
			$this->reflect = (new \ReflectionClass("\\pocketmine\\network\\RakLibInterface"))->getProperty("interface");
			$this->reflect->setAccessible(true);
		}
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this,"onTick"]), 5);
	}
	
	public function onDisable(){
		$this->getServer()->getScheduler()->cancelTasks($this);
	}

	public function onTick(){
		@mkdir($this->getDataFolder());
		$sn = (new Config($this->getDataFolder() . "ServerName.yml", Config::YAML, ["Name" => Color::YELLOW."[".Color::GOLD."%motd".Color::YELLOW."] ".Color::DARK_GREEN."Join: [".Color::GREEN."%onlineplayers".Color::DARK_GREEN."/".Color::GREEN."%maxplayers".Color::DARK_GREEN."]", "Port" => Color::GREEN."%version".Color::AQUA."ComeOn"]))->getAll();
//		foreach($this->getServer()->getNetwork()->getInterfaces() as $interface) $this->reflect->getValue($interface)->sendOption("name", "MCPE;".addcslashes(str_replace($before = ["%motd", "%onlineplayers", "%maxplayers", "%version", "%n"], $after = [$this->getServer()->getConfigString("motd", "Minecraft: PE Server"), count($this->getServer()->getOnlinePlayers()), $this->getServer()->getConfigString("max-players", 20), \pocketmine\MINECRAFT_VERSION_NETWORK, "\n"], $sn["Name"]), ";").";".Info::CURRENT_PROTOCOL.";".str_replace($before, $after, $sn["Port"]));
		foreach($this->getServer()->getNetwork()->getInterfaces() as $interface){
			$this->reflect->getValue($interface)->sendOption("name", 
				"MCPE;".addcslashes(str_replace($before = ["%motd", "%onlineplayers", "%maxplayers", "%version", "%n"], $after = [$this->getServer()->getMotd(), count($this->getServer()->getOnlinePlayers()), $this->getServer()->getMaxPlayers(), \pocketmine\MINECRAFT_VERSION_NETWORK, "\n"], $sn["Name"]), ";").";".
			 	Info::CURRENT_PROTOCOL.";".
				str_replace($before, $after, $sn["Port"]).";".
// \pocketmine\MINECRAFT_VERSION_NETWORK.";".
				count($this->getServer()->getOnlinePlayers()).";". 				 			 
				$this->getServer()->getMaxPlayers()
			);
		}
 //		$this->getServer()->getNetwork()->setName( str_replace($before = ["%motd", "%onlineplayers", "%maxplayers", "%version", "%n"], $after = [$this->getServer()->getConfigString("motd", "Minecraft: PE Server"), count($this->getServer()->getOnlinePlayers()), $this->getServer()->getConfigString("max-players", 20), \pocketmine\MINECRAFT_VERSION_NETWORK, "\n"], $sn["Name"]));
	}
}