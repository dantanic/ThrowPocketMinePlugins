<?php

namespace AntiBlockLauncher; 

use pocketmine\Player;
use pocketmine\utils\Utils;
use pocketmine\utils\TextFormat as Color;

class AntiBlockLauncher extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onEnable(){
		$this->times = [];
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDataPacketReceive(\pocketmine\event\server\DataPacketReceiveEvent $event){
 		$player = $event->getPlayer();
 		$packet = $event->getPacket();
 		//ProtocolInfo::LOGIN_PACKET = 0x82
		if($packet->pid() == 0x82 && !$player->loggedIn && !isset($this->times[$name = strtolower(Color::clean($packet->username))])){
			$player->setDataFlag(14, 0, true);
/*			$event->setCancelled();
 			$this->times[$name] = true;
			$pk = new ServerTPPacket();
			$pk->address = "rubyfarm.kr"; //(($ip = Utils::getIp()) === false) ? "127.0.0.1" : $ip;
			$pk->port = 19132; //$this->getServer()->getPort();
			$player->loggedIn = true;
  			$player->dataPacket($pk);
 			$player->loggedIn = false;
*/
		}
	}

	public function onPlayerPreLogin(\pocketmine\event\player\PlayerPreLoginEvent $event){
//		unset($this->times[strtolower($event->getPlayer()->getName())]);
	}
}