<?php

namespace BroadcastHook;

class BroadcastListener implements \pocketmine\command\CommandSender{
	private $perm;

 	public function sendMessage($message){
		\pocketmine\Server::getInstance()->getLogger()->info("[Listener] $message");
	}

	public function getServer(){
		return \pocketmine\Server::getInstance();
	}

	public function getName(){
		return "BroadcastListener";
	}


	public function isPermissionSet($name){
		return $this->perm->isPermissionSet($name);
	}

	public function hasPermission($name){
		return $this->perm->hasPermission($name);
	}

	public function addAttachment(\pocketmine\plugin\Plugin $plugin, $name = null, $value = null){
		return $this->perm->addAttachment($plugin, $name, $value);
	}

	public function removeAttachment(\pocketmine\permission\PermissionAttachment $attachment){
		$this->perm->removeAttachment($attachment);
	}

	public function recalculatePermissions(){
		$this->perm->recalculatePermissions();
	}

	public function getEffectivePermissions(){
		return $this->perm->getEffectivePermissions();
	}

	public function isOp(){
		return false;
	}

	public function setOp($value){
	}
}