<?php
namespace CommandBlock;

use pocketmine\utils\TextFormat as Color;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerChatvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\TranslationContainer as Translation;
use ShortCut\ShortCut;

class CommandBlock extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{ 
	const MODE_ADD = 0;
	const MODE_REMOVE = 2;
	const MODE_REMOVEMODE = 3;
	const MODE_BUILD = 4;
	const INFO_MODE = 0;
	const INFO_CMD = 1;
	private $data = [], $editTouch = [], $placed = [], $players = [];

	public function onEnable(){
		$this->loadData();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this, "onTick"]), 2);
	}

	public function onDisable(){
		$this->saveData();
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		$ik = $this->isKorean();
		if(!isset($sub[0]) || $sub[0] == ""){
			if(isset($this->editTouch[$name = $sender->getName()])){
				unset($this->editTouch[$name]);
				$sender->sendMessage(Color::YELLOW . "[CommandBlock] " . ($ik ? "커맨드블럭 편집모드가 해제됩니다." : "CommandBlock edit mode is disabled"));
				return true;
			}else{
				return false;
			}
		}
		switch(strtolower($sub[0])){
			case "add":
			case "a":
			case "추가":
				if(!$sender->hasPermission("commandblock.cmd.add")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}elseif(!$sender instanceof Player){
					$r = Color::RED . "[CommandBlock] " . ($ik ? "게임내에서만 실행해주세요." : "Please run this command in-game");					
				}elseif(isset($this->editTouch[$name = $sender->getName()]) && $this->editTouch[$name][self::INFO_MODE] == self::MODE_ADD){
					unset($this->editTouch[$name]);
					$r = Color::YELLOW . "[CommandBlock] " . ($ik ? "커맨드블럭 추가모드가 해제됩니다." : "CommandBlock add mode is disabled");
				}elseif(!isset($sub[1]) || $sub[1] == ""){
					$r = Color::RED .  "Usage: /CommandBlock Add(A) " . ($ik ? "<명령어>" : "<Command>");
				}else{
					$this->editTouch[$name] = [self::INFO_MODE => self::MODE_ADD, self::INFO_CMD => implode(" ", array_splice($sub, 1))];
					$r = Color::YELLOW . "[CommandBlock] " . ($ik ? "커맨드블럭을 추가할 블럭을 터치해주세요." : "Touch the block to add commandblock.");
				}
			break;
			case "remove":
			case "r":
			case "del":
			case "d":
			case "삭제":
			case "제거":
				if(!$sender->hasPermission("commandblock.cmd.remove")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}elseif(!$sender instanceof Player){
					$r = Color::RED . "[CommandBlock] " . ($ik ? "게임내에서만 실행해주세요." : "Please run this command in-game");					
				}elseif(isset($this->editTouch[$name = $sender->getName()]) && ($this->editTouch[$name][self::INFO_MODE] == self::MODE_REMOVE || $this->editTouch[$name][self::INFO_MODE] == self::MODE_REMOVEMODE)){
					unset($this->editTouch[$name]);
					$r = Color::YELLOW . "[CommandBlock] " . ($ik ? "커맨드블럭 제거 모드가 해제됩니다." : "CommandBlock remove mode is disabled");
				}else{
					$this->editTouch[$name] = [self::INFO_MODE => isset($sub[1]) && $sub[1] != "" ? self::MODE_REMOVEMODE : self::MODE_REMOVE];
					$r = Color::YELLOW . "[CommandBlock] " . ($ik ? "제거할 커맨드블럭을 터치해주세요." : "Touch the commandblock to remove.");
				}
			break;
			case "buildmode":
			case "build":
			case "edit":
			case "b":
			case "bm":
			case "e":
				if(!$sender->hasPermission("commandblock.cmd.buildmode")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}elseif(!$sender instanceof Player){
					$r = Color::RED . "[CommandBlock] " . ($ik ? "게임내에서만 실행해주세요." : "Please run this command in-game");
				}elseif(isset($this->editTouch[$name = $sender->getName()]) && $this->editTouch[$name][self::INFO_MODE] === self::MODE_BUILD){
					unset($this->editTouch[$name]);
					$r = Color::YELLOW . "[CommandBlock] " . ($ik ? "건축 모드를 비활성화했습니다." : "Disable the build mode");
				}else{
					$this->editTouch[$name] = [self::INFO_MODE => self::MODE_BUILD];
					$r = Color::YELLOW . "[CommandBlock] " . ($ik ? "건축 모드를 활성화했습니다." : "Enable the build mode");
				}
			break;
			case "reload":
				if(!$sender->hasPermission("commandblock.cmd.reload")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}else{
					$this->loadData();
					$r = Color::YELLOW . "[CommandBlock] " . ($ik ? "데이터를 로드했습니다." : "Load thedata");
				}
			break;
			case "save":
				if(!$sender->hasPermission("commandblock.cmd.save")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}else{
					$this->saveData();
					$r = Color::YELLOW . "[CommandBlock] " . ($ik ? "데이터를 저장했습니다." : "Save the data");
				}
			break;
			case "reset":
			case "리셋":
			case "초기화":
				if(!$sender->hasPermission("commandblock.cmd.reset")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}else{
					foreach($this->data as $pos => $this->commandblockInfo){
						$this->removeCommandBlock($pos);
					}
					$this->saveData();
					$r = Color::YELLOW . "[CommandBlock] " . ($ik ? "데이터를 리셋했습니다." : "Reset the data");
				}
			break;
			default:
				if(isset($this->editTouch[$name = $sender->getName()])){
					$r = Color::YELLOW . "[CommandBlock] " . ($ik ? "커맨드블럭 편집모드를 해제했습니다." : "CommandBlock Edit Mode Disable");
					unset($this->editTouch[$name]);
				}else{
					return false;
				}
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		return true;
	}

	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
		if((!isset($this->editTouch[$name = $event->getPlayer()->getName()]) || $this->editTouch[$name][self::INFO_MODE] !== self::MODE_BUILD) && isset($this->data[$this->pos2str($event->getBlock())])){
			$event->setCancelled();
 		}
	}

	public function onBlockPlace(\pocketmine\event\block\BlockPlaceEvent $event){
		if((!isset($this->editTouch[$name = $event->getPlayer()->getName()]) || $this->editTouch[$name][self::INFO_MODE] !== self::MODE_BUILD) && isset($this->data[$this->pos2str($event->getBlock())]) || isset($this->placed[$name])){
			$event->setCancelled();
			if(isset($this->placed[$name])){
				unset($this->placed[$name]);
			}
 		}
	}

	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		$player = $event->getPlayer();
 		if((!isset($this->editTouch[$name = $player->getName()]) || $this->editTouch[$name][self::INFO_MODE] !== self::MODE_BUILD) && $event->getAction() == 1){ //$event->getAction() == PlayerInteractEvent::RIGHT_CLICK_BLOCK (== 1)
			$block = $event->getBlock();
			$pos = $this->pos2str($block);
			$ik = $this->isKorean();
			if(isset($this->editTouch[$name])){
				switch($this->editTouch[$name][self::INFO_MODE]){
					case self::MODE_ADD:
						$this->addCommandBlock($pos, $this->editTouch[$name][self::INFO_CMD]);
						unset($this->editTouch[$name]);
						$player->sendMessage(Color::YELLOW . "[CommandBlock] " . ($ik ? "커맨드블럭을 추가했습니다." : "Added the commandblock."));
					break;
					case self::MODE_REMOVE:
						if(!isset($this->data[$pos]) && !isset($this->data[$pos = $this->pos2str($block->getSide($event->getFace()))])){
							$player->sendMessage(Color::RED . "[CommandBlock] " . ($ik ? "이곳에는 커맨드블럭이 없습니다." : "CommandBlock is not exist in here"));
						}else{
							$this->removeCommandBlock($pos);
							unset($this->editTouch[$name]);
							$player->sendMessage(Color::YELLOW . "[CommandBlock] " . ($ik ? "커맨드블럭을 제거했습니다." : "Removed the commandblock."));
						}
					break;
					case self::MODE_REMOVEMODE:
						if(!isset($this->data[$pos]) && !isset($this->data[$pos = $this->pos2str($block->getSide($event->getFace()))])){
							$player->sendMessage(Color::RED . "[CommandBlock] " . ($ik ? "이곳에는 커맨드블럭이 없습니다." : "CommandBlock is not exist in here"));
						}else{
							$this->removeCommandBlock($pos);
							$player->sendMessage(Color::YELLOW . "[CommandBlock] " . ($ik ? "커맨드블럭이 제거되었습니다.\n" . Color::YELLOW . "[CommandBlock] 다음 커맨드블럭을 터치하시거나 명령어를 다시 입력해 제거모드를 종료해주세요." : "CommandBlock is deleted \n" . Color::YELLOW . "[CommandBlock] Touch the next commandblock to delete or re-enter the command to disable the delete mode"));
						}
					break;
				}
				$event->setCancelled();
				if(!$this->isNewAPI() && $event->getItem()->isPlaceable()){
					$this->placed[$name] = true;
				}
			}elseif(isset($this->data[$pos]) || isset($this->data[$pos = $this->pos2str($block->getSide($event->getFace()))])){
				if($player->hasPermission("commandblock.use.touch")){
					$this->runCommand($player, $pos);
				}
				$event->setCancelled();
				if(!$this->isNewAPI() && $event->getItem()->isPlaceable()){
					$this->placed[$name] = true;
				}
			}
		}
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
		if(!$event->isCancelled() && stripos("/save-all", $command = $event->getMessage()) === 0){
			$this->checkSaveAll($event->getPlayer());
		}
	}

	public function checkSaveAll(\pocketmine\command\CommandSender $sender){
		if(($command =  $this->getServer()->getCommandMap()->getCommand("save-all")) instanceof \pocketmine\command\Command && $command->testPermissionSilent($sender)){
			$this->saveData();
			$sender->sendMessage(Color::YELLOW . "[CommandBlock] Saved data.");
		}
	}

	public function onTick(){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			if($player->hasPermission("commandblock.use.tread")){
				$player->y--;
				$pos = $this->pos2str($player);
				$player->y++;
				if(isset($this->data[$pos])){
					foreach($this->data[$pos] as $cmd){
						if(stripos($cmd, "@istread") !== false){
							$this->runCommand($player, $pos, false);
							break;
						}
					}
				}
			}
		}
	}

	public function addCommandBlock($pos, $command){
		if(!isset($this->data[$pos])){
			$this->data[$pos] = [];
		}
		$this->data[$pos][] = $command;
	}

	public function removeCommandBlock($pos){
		if(isset($this->data[$pos])){
	 		unset($this->data[$pos]);
	 	}
	}

	public function runCommand($player, $pos, $isTouch = true){
		if(isset($this->data[$pos])){
			if(!isset($this->players[$name = $player->getName()])){
				$this->players[$name] = [];
			}
			if(!isset($this->players[$name][$pos])){
				$this->players[$name][$pos] = 0;
			}
			if(microtime(true) - $this->players[$name][$pos] > 1){
				$explode = explode(":", ":" . $pos);
				$this->players[$name][$pos] = microtime(true);
				foreach($this->data[$pos] as $str){
					if((stripos($str, "@istread") === false) == $isTouch){
						ShortCut::addShortCut($rId = "%commamdblock_" . bcadd("1095216660480", mt_rand(0, 0x7fffffff)), str_ireplace(["@istread", "@blockx", "@blocky", "@blockz"], $explode, $str));
						$this->getServer()->getPluginManager()->callEvent($ev = new PlayerCommandPreprocessEvent($player, "/" . $rId));
						ShortCut::removeShortCut($rId);
						if(!$ev->isCancelled()){
							$this->getServer()->dispatchCommand($player, substr($ev->getMessage(), 1));
						}
					}
				}
			}
		}
	}

	public function pos2str(\pocketmine\level\Position $pos){
		return floor($pos->x) . ":" . floor($pos->y) . ":" . floor($pos->z) . ":" . $pos->getLevel()->getFolderName();
	}

	public function loadData(){
		@mkdir($folder = $this->getDataFolder());
		if(!file_exists($path = $folder . "CommandBlocks.sl")){	
			file_put_contents($path, serialize([]));
		}
		$this->data = unserialize(file_get_contents($path));
	}

	public function saveData(){
		@mkdir($folder = $this->getDataFolder());
		file_put_contents($folder . "CommandBlocks.sl", serialize($this->data));
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}

	public function isNewAPI(){
		return $this->getServer()->getApiVersion() !== "1.12.0";
	}
}