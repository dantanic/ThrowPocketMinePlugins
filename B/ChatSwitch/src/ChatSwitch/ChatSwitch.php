<?php

namespace ChatSwitch;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;

class ChatSwitch extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onEnable(){
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($this->cs[$name = strtolower($sender->getName())])) $this->cs[$name] = true;
		$this->cs[$name] = !$this->cs[$name];
		$sender->sendMessage(Color::YELLOW . "[ChatSwitch] " . ($this->isKorean() ? "채팅을 받" . ($this->cs[$name] ? "" : "지 않") . "습니다." : ($this->cs[$name] ? "" : "Not ") . "receive the chat"));
		$this->saveYml();
		return true;
	}

	public function onPlayerChat(\pocketmine\event\player\PlayerChatEvent $event){
		$player = $event->getPlayer();
		if(!isset($cs[$name = strtolower($player->getName())])) $this->cs[$name] = true;
		if(!$this->cs[$name]){
			$player->sendMessage(Color::RED . "[ChatSwitch] " . ($this->isKorean() ? "당신은 채팅을 받지않습니다." : "You are not receive the chat"));
			$event->setCancelled();
			return;
		}
		foreach(($recipients = $event->getRecipients()) as $key => $recipient){
			$name = strtolower($recipient->getName());
			if(!isset($this->cs[$name])){
				$this->cs[$name] = true;
				$this->saveYml();
			}elseif(!$this->cs[$name]) unset($recipients[$key]);
		}
		$event->setRecipients($recipients);
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->cs = (new Config($this->getDataFolder() . "ChatSwitch.yml", Config::YAML))->getAll();
	}

	public function saveYml(){
		$cs = new Config($this->getDataFolder() . "ChatSwitch.yml", Config::YAML);
		$cs->setAll($this->cs);
		$cs->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}