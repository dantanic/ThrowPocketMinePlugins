<?php

namespace SafetyStop;

use pocketmine\utils\TextFormat as Color;

class SafetyStop extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
 	}

	/**
	 * @priority HIGHEST
	 */
	public function onServerCommandProcess(\pocketmine\event\server\ServerCommandEvent $event){
		if(!$event->isCancelled()){
			if(stripos("stop", $event->getCommand()) === 0){
				$this->checkStop($event->getSender());
			}
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onRemoteServerCommand(\pocketmine\event\server\RemoteServerCommandEvent $event){
		if(!$event->isCancelled()){
			if(stripos("stop", $event->getCommand()) === 0){
				$this->checkStop($event->getSender());
			}
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onPlayerCommandPreprocess(\pocketmine\event\player\PlayerCommandPreprocessEvent $event){
		if(!$event->isCancelled()){
			if(stripos("/stop", $event->getMessage()) === 0){
				$this->checkStop($event->getPlayer());
			}
		}
	}

	public function checkStop(\pocketmine\command\CommandSender $sender){
		if(($command =  $this->getServer()->getCommandMap()->getCommand("stop")) instanceof \pocketmine\command\Command && $command->testPermissionSilent($sender)){
			$logger = $this->getLogger();
			$logger->notice(($ik = $this->getServer()->getLanguage()->getName() == "\"한국어\"") ? "안전종료 시작" : "Safety stop start");
			$logger->notice("Disabling all plugins");
	 		$this->getServer()->getPluginManager()->disablePlugins();	
			$logger->notice("Kicking all players");
			foreach($this->getServer()->getOnlinePlayers() as $player){
				$player->close($player->getLeaveMessage(), $this->getServer()->getProperty("settings.shutdown-message", "Server closed"));
			}
			$logger->notice("Unloading all levels");
			foreach($this->getServer()->getLevels() as $level){
				$this->getServer()->unloadLevel($level, true);
			}
			$logger->notice($ik ? "안전종료 완료" : "Safety stop complete");
		}
	}
}