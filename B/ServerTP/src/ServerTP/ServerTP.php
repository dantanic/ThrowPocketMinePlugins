<?php

namespace ServerTP;

class ServerTP extends \pocketmine\plugin\PluginBase{
	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[2])) return false;
 		if(($player = $this->getServer()->getPlayer($sub[0])) == null){
			$sender->sendMessage("[ServerTP] $sub[0] is invalid Player");
		}elseif(!is_numeric($sub[2])){
			$sender->sendMessage("[ServerTP] $sub[2] id invaild Port");
		}else{
			if(preg_match("/^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$/", $sub[1]) > 0){
				$ip = $sub[1];
			}else{
				$ip = gethostbyname($sub[1] = strtolower($sub[1]));
				if($sub[1] == $ip){
					$sender->sendMessage("[ServerTP] $sub[1] is invalid IP");
					return true;
				}
			}
			$pk = new ServerTPPacket();
			$pk->address = $ip;
			$pk->port = $sub[2];
			$sender->sendMessage($cause = "[ServerTP] ".$player->getDisplayName()." teleport to $ip:$sub[2]");
			$player->dataPacket($pk);
		}
		return true;
	}
}