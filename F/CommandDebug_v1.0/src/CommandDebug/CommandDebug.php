<?php
namespace CommandDebug;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;

class CommandDebug extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{

	public function onEnable(){
		$this->loadData();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDisable(){
		$this->saveData();
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		$ik = $this->isKorean();
		if(!isset($sub[0]) || $sub[0] == ""){
			return false;
		}
		switch(strtolower($sub[0])){
			case "add":
			case "a":
			case "추가":
				if(!$sender->hasPermission("commanddebug.cmd.add")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}elseif(!isset($sub[1]) || $sub[1] == ""){
					$r = Color::RED .  "Usage: /CommandDebug Add(A) " . ($ik ? "<명령어>" : "<Command>");
				}elseif(in_array($sub[1] = strtolower($sub[1]), $this->data)){
					$r = Color::RED .  "[CommandDebug] $sub[1]" . ($ik ? "는 이미 존재합니다." : "is already exists");
				}else{
					$this->data[] = $sub[1];
					$r = Color::YELLOW . "[CommandDebug] " . ($ik ? "커맨드디버그 예외를 추가했습니다." : "Added debug exception.");
				}
			break;
			case "remove":
			case "r":
			case "del":
			case "d":
			case "삭제":
			case "제거":
				if(!$sender->hasPermission("commanddebug.cmd.remove")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}elseif(!in_array($sub[1] = strtolower($sub[1]), $this->data)){
					$r = Color::RED .  "[CommandDebug] $sub[1]" . ($ik ? "는 존재하지않습니다." : "is not exists");
				}else{
					unset($this->data[array_search($sub[1])]);
					$r = Color::YELLOW . "[CommandDebug] " . ($ik ? "커맨드디버그 예외를 제거했습니다." : "Removed debug exception.");
				}
			break;
			case "reload":
				if(!$sender->hasPermission("commanddebug.cmd.reload")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}else{
					$this->loadData();
					$r = Color::YELLOW . "[CommandDebug] " . ($ik ? "데이터를 로드했습니다." : "Load thedata");
				}
			break;
			case "save":
				if(!$sender->hasPermission("commanddebug.cmd.save")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}else{
					$this->saveData();
					$r = Color::YELLOW . "[CommandDebug] " . ($ik ? "데이터를 저장했습니다." : "Save the data");
				}
			break;
			case "reset":
			case "리셋":
			case "초기화":
				if(!$sender->hasPermission("commanddebug.cmd.reset")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}else{
					$this->data = [];
					$r = Color::YELLOW . "[CommandDebug] " . ($ik ? "데이터를 리셋했습니다." : "Reset the data");
				}
			break;
			default:
				return false;
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		return true;
	}

	/**
	 * @priority HIGHEST
	 */
	public function onServerCommandProcess(\pocketmine\event\server\ServerCommandEvent $event){
		if(!$event->isCancelled() && stripos("save-all", $command = $event->getCommand()) === 0){
			$this->checkSaveAll($event->getSender());
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onRemoteServerCommand(\pocketmine\event\server\RemoteServerCommandEvent $event){
		if(!$event->isCancelled() && stripos("save-all", $command = $event->getCommand()) === 0){
			$this->checkSaveAll($event->getSender());
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onPlayerCommandPreprocess(\pocketmine\event\player\PlayerCommandPreprocessEvent $event){
		if(!$event->isCancelled() && strpos($message = $event->getMessage(), "/") === 0 && !in_array($first = strtolower(explode(" ", $command = substr($message, 1))[0]), $this->data) && $this->getServer()->getCommandMap()->getCommand($first)){
			$this->getLogger()->notice(Color::YELLOW . $event->getPlayer()->getName() . ($event->getPlayer()->isOp() ? Color::RED : Color::BLUE)." : $command");
		}
		if(!$event->isCancelled() && stripos("/save-all", $command = $event->getMessage()) === 0){
			$this->checkSaveAll($event->getPlayer());
		}
	}

	public function checkSaveAll(\pocketmine\command\CommandSender $sender){
		if(($command =  $this->getServer()->getCommandMap()->getCommand("save-all")) instanceof \pocketmine\command\Command && $command->testPermissionSilent($sender)){
			$this->saveData();
			$sender->sendMessage(Color::YELLOW . "[CommandDebug] Saved data.");
		}
	}

	public function loadData(){
		@mkdir($folder = $this->getDataFolder());
		$this->data = (new Config($folder . "CommandDebug.yml", Config::YAML))->getAll();
	}

	public function saveData(){
		@mkdir($folder = $this->getDataFolder());
		ksort($this->data);
		$data = new Config($folder . "CommandDebug.yml", Config::YAML, ["login", "register"]);
		$data->setAll($this->data);
		$data->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "\"한국어\"";
	}
}