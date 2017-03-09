<?php

namespace BroadcastHook;

class BroadcastHook extends \pocketmine\plugin\PluginBase{
	public function onLoad(){
		$this->getServer()->getPluginManager()->subscribeToPermission(\pocketmine\Server::BROADCAST_CHANNEL_USERS, new BroadcastListener()); 		
	}
}
/*
	public function broadcast($message, $permissions){
		$recipients = [];
		foreach(explode(";", $permissions) as $permission){
			foreach($this->pluginManager->getPermissionSubscriptions($permission) as $permissible){
				if($permissible instanceof CommandSender and $permissible->hasPermission($permission)){
					$recipients[spl_object_hash($permissible)] = $permissible; // do not send messages directly, or some might be repeated
				}
			}
		}
		foreach($recipients as $recipient){
			$recipient->sendMessage($message);
		}
		return count($recipients);
	}
*/