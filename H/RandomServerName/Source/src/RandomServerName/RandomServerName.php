<?php
namespace RandomServerName;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat as Color;
use RandomServerName\task\SetServerNameTask;

class RandomServerName extends PluginBase{
	private $data, $colorList = [
		Color::DARK_BLUE,
		Color::DARK_GREEN,
		Color::DARK_AQUA,
		Color::DARK_RED,
		Color::DARK_PURPLE,
		Color::GOLD,
//		Color::GRAY,
//		Color::DARK_GRAY,
		Color::BLUE,
		Color::GREEN,
		Color::AQUA,
		Color::RED,
		Color::LIGHT_PURPLE,
		Color::YELLOW,
		Color::WHITE
	];

	public function onEnable(){
		$this->loadData();
 		$this->getServer()->getScheduler()->scheduleRepeatingTask(new SetServerNameTask($this), 5);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		$this->loadData();
		$sender->sendMessage(Color::YELLOW . "[RandomServerName] " . ($this->isKorean() ? "데이터를 로드했습니다." : "Load the data"));
		return true;
	}

	public function getServerName(){
		return str_ireplace(
			["{PLAYERS}", "{MAXPLAYERS}", "{MOTD}", "{VERSION}", "{RCOLOR}"], 
			[count($this->getServer()->getOnlinePlayers()), $this->getServer()->getMaxPlayers(), $this->getServer()->getMotd(), \pocketmine\MINECRAFT_VERSION_NETWORK, $this->colorList[array_rand($this->colorList)]],
			$this->data[array_rand($this->data)]
		);		
	}

	public function loadData(){
		@mkdir($folder = $this->getDataFolder());
		if(!file_exists($path = $folder . "ServerName List.txt")){	
			file_put_contents($path, 
				"{RCOLOR}{MOTD} \n" . 
				"{MOTD} {RCOLOR} !!! \n"
			);
		}
		$this->data = explode("\n", file_get_contents($path));
		file_put_contents($folder . "Changes List.txt",
			"# {PLAYERS} = Player count in server \n" . 
			"# {MAXPLAYERS} = Max player count \n" . 
			"# {MOTD} = Server MOTD in server.properties \n" . 
			"# {VERSION} = MineCraft Version \n" . 
			"# {RCOLOR} = Random Color \n"
		);
	}
}