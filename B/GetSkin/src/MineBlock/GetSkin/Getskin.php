<?php

namespace MineBlock\GetSkin;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class GetSkin extends PluginBase implements Listener{
	public function onEnable(){
		$this->player = [];
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this,"onTick"]), 20);
	}
	
	public function onDisable(){
		$this->getServer()->getScheduler()->cancelTasks($this);
	}

	public function onTick(){
		foreach($this->getServer()->getOnlinePlayers() as $p){
			if($p->dead) continue;
			if(!isset($this->player[$n = $p->getName()]) || $this->player[$n]->closed == true){
				$this->player[$n] = new FroatingText($this, $p, $p->getName());//, base64_encode($p->getSkinData()));
			}
//			$this->player[$n]->setSkin(	base64_decode($this->gs[array_rand($this->gs)]));
		}
	}

	public function onPlayerInteract(PlayerInteractEvent $event){
		$p = $event->getPlayer();
		$skin = $p->getSkinData();
		$p->sendMessage("Skin Saved");
		$this->gs[] = base64_encode($skin);
 		$this->saveYml();
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->gs = (new Config($this->getDataFolder() . "GetSkin.yml", Config::YAML))->getAll();
	}

	public function saveYml(){
		asort($this->gs);
		$gs = new Config($this->getDataFolder() . "GetSkin.yml", Config::YAML);
		$gs->setAll($this->gs);
		$gs->save();
	}
}