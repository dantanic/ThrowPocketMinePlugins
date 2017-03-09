<?php

namespace Farm;

use pocketmine\block\Block;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat as Color;
use pocketmine\event\TranslationContainer as Translation;

class FarmAPI extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	const FARMS = 0;
	const PLAYERS = 1;
	const FARM_LEVEL = 0;
	const FARM_VISIT_ALLOW = 1;
	const FARM_VISIT_NOTICE = 2;
	const FARM_VISIT_MESSAGE = 3;
	const FARM_WATER_FLOW = 4;
	const FARM_PVP_ALLOW = 5;
	const FARM_PICKUP_ITEM = 6;
	const FARM_SPAWN = 7;
	const NETHER_WART = 115;
	const COCOA_BEANS = 127;

	private $data = [], $invite = [];

 	public function onLoad(){

 		\pocketmine\level\generator\Generator::addGenerator(FarmGenerator::class, "farmland");
		if($this->getServer()->isLevelGenerated("farm")){
			$this->getServer()->loadLevel("farm");
		}
	}

	public function onEnable(){
		if(!$this->getServer()->isLevelLoaded("farm") && !$this->getServer()->isLevelGenerated("farm")){
			$this->getServer()->generateLevel("farm", null, FarmGenerator::class);
		}else{
			$this->getServer()->loadLevel("farm");
		}
		$this->getServer()->getLogger()->notice(Color::GREEN . "Find economy plugin...");
		$pluginManager = $this->getServer()->getPluginManager();
		if(!($this->money = $pluginManager->getPlugin("PocketMoney")) && !($this->money = $pluginManager->getPlugin("EconomyAPI")) && !($this->money = $pluginManager->getPlugin("MassiveEconomy")) && !($this->money = $pluginManager->getPlugin("Money"))){
			$this->getServer()->getLogger()->notice(Color::RED . "Failed find economy plugin...");
		}else{
			$this->getServer()->getLogger()->notice(Color::GREEN . "Finded economy plugin : " . $this->money->getName());
		}
		$this->loadData();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDisable(){
		$this->saveData();
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[0]) || $sub[0] == ""){
			return false;
		}elseif(($cmd = strtolower($cmd)) == "farm"){
			switch(strtolower($sub[0])){
				case "list":
					if(!$sender->hasPermission("farm.api.cmd.list")){
						$r = new Translation(Color::RED . "%commands.generic.permission");
					}else{
						$levels = [];
						foreach($this->data[self::PLAYERS] as $name => $data){
							$levels[$name] = $data[self::FARM_LEVEL];
						}
						asort($levels);
						$lists = array_chunk($levels, 5);
						$r = Color::YELLOW . "[농장] 농장 목록 (페이지: " . ($page = min(isset($sub[1]) && is_numeric($sub[1]) && isset($lists[$sub[1] - 1]) ? $sub[1] : 1, count($lists))). "/" . count($lists) . ") (" . count($levels) . ")";
						if(isset($lists[$page - 1])){
							$keys = array_keys($this->data[self::PLAYERS]);
							foreach($lists[$page - 1] as $key => $level){
								$r .= "\n" . Color::GOLD . "    [" . (($farmKey = (($page - 1) * 5 + $key)) + 1) .  "] " . $keys[$farmKey] . " : $level";
							}
						}
					}
				break;
				case "save":
					if(!$sender->hasPermission("farm.api.cmd.save")){
						$r = new Translation(Color::RED . "%commands.generic.permission");
					}else{
						$this->saveData();
						$r = Color::YELLOW . "[농장] 데이터를 저장했습니다.";
					}
				break;
				default:
					return false;
				break;
			}
		}elseif($cmd == "농장"){
			if(!$sender instanceof Player){
				$r = Color::RED . "[농장] 게임내에서만 실행해주세요.";
			}elseif(!isset($this->data[self::PLAYERS][$name = strtolower($sender->getName())])){
				$this->giveFarm($name);
				$r = Color::RED . "[농장] 농장이 없습니다. 농장을 지급합니다.";
			}else{
				switch(strtolower($sub[0])){
					case "이동":
						if(!$sender->hasPermission("farm.api.cmd.user.move")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}else{
							$sender->teleport($this->getFarmSpawn($name));
							$r = Color::YELLOW . "[농장] 나의 팜으로 이동하였습니다";
						}
					break;
					case "스폰설정":
						if(!$sender->hasPermission("farm.api.cmd.user.setspawn")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}elseif(!$this->isOwner($sender, $sender)){
							$r = Color::RED . "[농장] 자신의 농장 안에서 사용해주세요.";
						}else{
							$this->data[self::PLAYERS][$name][self::FARM_SPAWN] = ((floor($sender->x * 10) % 1100) * 0.1) . ":" . (floor($sender->y * 10) * 0.1) . ":" . ((floor($sender->z * 10) % 1100) * 0.1);
							$r = Color::YELLOW . "[농장] 농장의 스폰포인트가 변경되었습니다.";
						}
					break;
					case "레벨업":
						if(!$sender->hasPermission("farm.api.cmd.user.levelup")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}elseif(($level = $this->getLevel($sender)) >= 10){
							$r = Color::RED . "[농장] 이미 최대 레벨입니다.";							
						}elseif(($money = $this->getMoney($sender)) < ($price = [0, 50000, 100000, 200000, 400000, 800000, 1600000, 3200000, 6400000, 12800000][$this->getLevel($sender)])){
							$r = Color::RED . "[농장] 레벨업에 필요한 돈이 부족합니다. 필요금액: $price, 소유금: $money";														
						}else{
							$sender->sendMessage(Color::YELLOW . "[농장] " . $price . "원을 소모하고 농장의 레벨업을 시작합니다.");
							$this->giveMoney($sender, -$price);
							$this->levelUp($sender);
							$r = Color::YELLOW . "[농장] 농장의 레벨이 성공적으로 상승했습니다. 이제 Lv." . $this->getLevel($sender) . "이 되어 " . ["", "밀", "비트", "당근", "감자", "선인장", "사탕수수", "수박", "호박", "코코아콩", "네더와트"][$this->getLevel($sender)] . "을 키우실 수 있습니다.";
						}
					break;
					case "방문":
						if(!$sender->hasPermission("farm.api.cmd.user.visit")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}elseif(!isset($sub[1]) || $sub[1] == ""){
							$r = Color::RED . "Usage: /농장 이동 <플레이어명>";
						}elseif(!isset($this->data[self::PLAYERS][$sub[1] = strtolower($sub[1])])){
							$r = Color::RED . "[농장] $sub[1]은(는) 잘못된 플레이아명입니다.";
						}elseif(!$this->data[self::PLAYERS][$sub[1]][self::FARM_VISIT_ALLOW]){
							$r = Color::RED . "[농장] $sub[1]님의 농장은 방문을 허용하지않았습니다";
						}else{
							if($this->data[self::PLAYERS][$sub[1]][self::FARM_VISIT_NOTICE] && ($player = $this->getServer()->getPlayerExact($sub[1])) instanceof Player && $sender !== $player){
								$player->sendMessage(Color::AQUA . "[농장] " . $sender->getName() . "님이 농장에 방문하셨습니다.");
							}
							$sender->teleport($this->getFarmSpawn($sub[1]));
							$r = Color::YELLOW . "[농장] $sub[1]님의 농장에 방문하였습니다.";
							if($name !== $sub[1]){
								$r .= "\n" . Color::AQUA . "[농장] " . $this->data[self::PLAYERS][$sub[1]][self::FARM_VISIT_MESSAGE];
							}
						}
					break;
					case "초대":
						if(!$sender->hasPermission("farm.api.cmd.user.invite")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}elseif(!($player = $this->getServer()->getPlayer($sub[1] = strtolower($sub[1]))) instanceof Player){
							$r = Color::RED . "[농장] $sub[1]은(는) 잘못된 플레이어명입니다.";							
						}elseif($player === $sender){
							$r = Color::RED . "[농장] 자기자신은 초대할 수 없습니다.";
						}elseif(isset($this->invite[$playerName = strtolower($player->getName())]) && in_array($name, $this->invite[$playerName])){
							$r = Color::RED . "[농장] 이미 초대하셨습니다. 기디려주세요.";							
						}else{
							if(!isset($this->invite[$playerName])){
								$this->invite[$playerName] = [$name];
							}else{
								$this->invite[$playerName][] = $name;
							}
							$player->sendMessage(Color::AQUA . "[농장] " . $sender->getName() . "님이 농장에 초대하셨습니다. \n" . Color::AQUA . "[농장] 수락하신다면 " . Color::DARK_AQUA . "/농장 수락" . Color::AQUA . "을 입력해주세요.");
							$r = Color::YELLOW . "[농장] $sub[1]님을 농장에 초대하였습니다.";
						}
					break;
					case "수락":
						if(!$sender->hasPermission("farm.api.cmd.user.accept")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}elseif(!isset($this->invite[$name]) || count($this->invite[$name]) == 0){
							$r = Color::RED . "[농장] 초대받은 농장이 없습니다.";							
						}else{
							if(!isset($sub[1]) || $sub[1] == ""){
								if(count($this->invite[$name]) == 1){
									$inviteName = array_shift($this->invite[$name]);
								}else{
									$r = Color::RED . "[농장] 당신은 초대받은 농장이 한곳이 아닙니다. 정확하게 입력해 주세요. " . Color::DARK_RED . "/농장 수락 <플레이어명>\n" . Color::RED . "[농장] 당신이 초대받은 농장 : " . implode(", ", $this->invite[$name]);
								}
							}elseif(!in_array($sub[1] = strtolower($sub[1]), $this->invite[$name])){
								$r = Color::RED . "[농장] 당신은 $sub[1]님에게 초대받지 않았습니다";
							}else{
								$inviteName = $sub[1];
							}
							if(isset($inviteName)){
								if($this->data[self::PLAYERS][$inviteName][self::FARM_VISIT_NOTICE] && ($player = $this->getServer()->getPlayerExact($inviteName)) instanceof Player){
									$player->sendMessage(Color::AQUA . "[농장] " . $sender->getName() . "님이 농장에 방문하셨습니다.");
								}
								$sender->teleport($this->getFarmSpawn($inviteName));
								unset($this->invite[$name][array_search($inviteName, $this->invite[$name])]);
								$r = Color::YELLOW . "[농장] " . $inviteName . "님의 농장에 방문하였습니다.";
								$r .= "\n" . Color::AQUA . "[농장] " . $this->data[self::PLAYERS][$inviteName][self::FARM_VISIT_MESSAGE];
							}
						}
					break;
					case "방문허용":
						if(!$sender->hasPermission("farm.api.cmd.user.visitallow")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}else{
							$this->data[self::PLAYERS][$name][self::FARM_VISIT_ALLOW] = !$this->data[self::PLAYERS][$name][self::FARM_VISIT_ALLOW];
							$r = Color::YELLOW . "[농장] 이제 다른 플레이어가 농장을 방문 할 수 " . ($this->data[self::PLAYERS][$name][self::FARM_VISIT_ALLOW] ? "있" : "없") . "습니다.";
						}
					break;
					case "방문알림":
						if(!$sender->hasPermission("farm.api.cmd.user.visitnotice")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}else{
							$this->data[self::PLAYERS][$name][self::FARM_VISIT_NOTICE] = !$this->data[self::PLAYERS][$name][self::FARM_VISIT_NOTICE];
							$r = Color::YELLOW . "[농장] 이제 다른 플레이어가 방문할 시에 알림을 표시" . ($this->data[self::PLAYERS][$name][self::FARM_VISIT_NOTICE] ? "합" : "하지 않습") . "니다.";
						}
					break;
					case "방문메세지":
						if(!$sender->hasPermission("farm.api.cmd.user.visitmessage")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}else{
							$this->data[self::PLAYERS][$name][self::FARM_VISIT_MESSAGE] = implode(" ", array_splice($sub, 1));
							$r = Color::YELLOW . "[농장] 방문 메세지가 변경되었습니다" . "\n " . Color::YELLOW . "테스트: " . Color::AQUA . "[농장] " . $this->data[self::PLAYERS][$name][self::FARM_VISIT_MESSAGE];
						}
					break;
					case "물흐름":
						if(!$sender->hasPermission("farm.api.cmd.user.waterflow")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}else{
							$this->data[self::PLAYERS][$name][self::FARM_WATER_FLOW] = !$this->data[self::PLAYERS][$name][self::FARM_WATER_FLOW];
							$r = Color::YELLOW . "[농장] 이제 농장 안에서 물이 흐" . ($this->data[self::PLAYERS][$name][self::FARM_WATER_FLOW] ? "릅" : "르지 않습") . "니다.";
						}
					break;
					case "전투허용":
						if(!$sender->hasPermission("farm.api.cmd.user.pvpallow")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}else{
							$this->data[self::PLAYERS][$name][self::FARM_PVP_ALLOW] = !$this->data[self::PLAYERS][$name][self::FARM_PVP_ALLOW];
							$r = Color::YELLOW . "[농장] 이제 농장 안에서 전투가 " . ($this->data[self::PLAYERS][$name][self::FARM_PVP_ALLOW] ? "" : "불") . "가능합니다.";							
						}
					break;
					case "아이템줍기허용":
						if(!$sender->hasPermission("farm.api.cmd.user.pickupitemallow")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}else{
							$this->data[self::PLAYERS][$name][self::FARM_PICKUP_ITEM] = !$this->data[self::PLAYERS][$name][self::FARM_PICKUP_ITEM];
							$r = Color::YELLOW . "[농장] 이제 다른 플레이어가 농장의 아이템을 주울 수 " . ($this->data[self::PLAYERS][$name][self::FARM_PICKUP_ITEM] ? "있" : "없") . "습니다.";
						}
					break;
					case "정보":
						if(!$sender->hasPermission("farm.api.cmd.user.info")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}elseif(!$this->isFarm($sender)){
							$r = Color::RED . "[농장] 이곳은 농장이 아닙니다";
						}elseif(($ownerName = $this->getOwnerByPosition($sender)) === false){
							$r = Color::RED . "[농장] 이 농장은 주인이 없습니다";
						}else{
							$r = Color::YELLOW . "[농장] 이 농장의 주인은 " . $ownerName . "입니다.\n  " . 
								Color::GOLD . "농장 레벨: Lv." . $this->data[self::PLAYERS][$ownerName][self::FARM_LEVEL] . ", 스폰포인트: " . $this->data[self::PLAYERS][$ownerName][self::FARM_SPAWN] . "\n" . 
								Color::GOLD . "방문메세지: " . Color::AQUA . $this->data[self::PLAYERS][$ownerName][self::FARM_VISIT_MESSAGE] . "\n  " . 
								Color::GOLD . "토글 설정: " . 
									($this->data[self::PLAYERS][$ownerName][self::FARM_VISIT_ALLOW] ? Color::GREEN . "방문허용" : Color::RED . "방문비허용") . ", " . 
									($this->data[self::PLAYERS][$ownerName][self::FARM_VISIT_NOTICE] ? Color::GREEN . "방문알림" : Color::RED . "방문무시") . ", " . 
									($this->data[self::PLAYERS][$ownerName][self::FARM_PVP_ALLOW] ? Color::GREEN . "전투허용" : Color::RED . "전투비허용") . ", " . 
									($this->data[self::PLAYERS][$ownerName][self::FARM_PICKUP_ITEM] ? Color::GREEN . "아이템줍기허용" : Color::RED . "아이템줍기비허용") . ", " . 
									($this->data[self::PLAYERS][$ownerName][self::FARM_WATER_FLOW] ? Color::GREEN . "물흐름허용" : Color::RED . "물흐름비허용");
 						}
					break;
					case "도움말":
						if(!$sender->hasPermission("farm.api.cmd.user.help")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}else{
							
						}
					break;
					default:
						return false;
					break;
				}
			}
		}
		if(isset($r)){
			$sender->sendMessage($r);
		}
		return true;
	}

	public function onPlayerBucketFill(\pocketmine\event\player\PlayerBucketFillEvent $event){
		$player = $event->getPlayer();
		if($this->isFarmWorld($player) && !$player->hasPermission("farm.api.block.interact") && (!$this->isFarm($player) || !$this->isOwner($player, $player))){
			$event->setCancelled();
		}
	}

	public function onPlayerJoin(\pocketmine\event\player\PlayerJoinEvent $event){
		$player = $event->getPlayer();
		if(!isset($this->data[self::PLAYERS][strtolower($player->getName())])){
			$this->giveFarm($player);
			$player->sendMessage(Color::RED . "[농장] 농장이 없습니다. 농장을 지급합니다.");
		}
	}

	public function onEntityDamage(\pocketmine\event\entity\EntityDamageEvent $event){
		if(!$event->isCancelled() && ($entity = $event->getEntity()) instanceof Player && $this->isFarmWorld($entity) && $event instanceof \pocketmine\event\entity\EntityDamageByEntityEvent && ($damager = $event->getDamager()) instanceof Player && !$damager->hasPermission("farm.api.pvp") && (!$this->isFarm($entity) || !$this->data[self::PLAYERS][$this->getOwnerByPosition($entity)][self::FARM_PVP_ALLOW])){
			$event->setCancelled();
		}
	}

	public function onPlayerMove(\pocketmine\event\player\PlayerMoveEvent $event){
		$player = $event->getPlayer();
		if($this->isFarmWorld($to = $event->getTo()) && !$player->hasPermission("farm.api.move") && ($this->getFarmIndexByPosition($event->getFrom()) !== $this->getFarmIndexByPosition($to) && (!$this->isFarm($to) || !$this->isOwner($to, $player)) || !$this->isInFarm($to, $this->getOwnerByPosition($player)))){
			$event->setCancelled();
		}
	}

	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if($this->isFarmWorld($block) && !$player->hasPermission("farm.api.block.interact") && (!$this->isFarm($block) || !$this->isOwner($block, $player) || !$this->isInFarm($block, $player))){
			$event->setCancelled();
		}
	}

	public function onBlockPlace(\pocketmine\event\block\BlockPlaceEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if($this->isFarmWorld($block) && !$player->hasPermission("farm.api.block.place") && (!$this->isFarm($block) || !$this->isOwner($block, $player) || !$this->isInFarm($block, $player))){
			$event->setCancelled();
		}
	}

	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if($this->isFarmWorld($block) && !$player->hasPermission("farm.api.block.break") && (!$this->isFarm($block) || !$this->isOwner($block, $player) || !$this->isInFarm($block, $player))){
			$event->setCancelled();
		}
	}

	public function onBlockUpdate(\pocketmine\event\block\BlockUpdateEvent $event){
		$block = $event->getBlock();
		if($this->isFarmWorld($block)){
			if(!$this->isInFarm($block, $ownerName = $this->getOwnerByPosition($block))){
				$event->setCancelled();
			}elseif(($block->getID() == 8 || $block->getID() == 9) && !$this->data[self::PLAYERS][$ownerName][self::FARM_WATER_FLOW]){
				$event->setCancelled();
			}
		}
	}

	public function onBlockGrow(\pocketmine\event\block\BlockGrowEvent $event){
		$block = $event->getBlock();
		if($this->isFarmWorld($block)){
			if(!$this->isInFarm($block, $ownerName = $this->getOwnerByPosition($block))){
				$event->setCancelled();
			}elseif(($block->y != 4 && in_array($block->getID(), [Block::WHEAT_BLOCK, Block::BEETROOT_BLOCK, Block::CARROT_BLOCK, Block::POTATO_BLOCK, Block::MELON_STEM, Block::PUMPKIN_STEM, self::COCOA_BEANS, self::NETHER_WART])) || 
				($block->y > 6 && in_array($block->getID(), [Block::CACTUS, Block::SUGARCANE_BLOCK])) || 
				$this->getLevel($ownerName) <= array_search($block->getID(), [Block::WHEAT_BLOCK, Block::BEETROOT_BLOCK, Block::CARROT_BLOCK, Block::POTATO_BLOCK, Block::CACTUS, Block::SUGARCANE_BLOCK, Block::MELON_STEM, Block::PUMPKIN_STEM, self::COCOA_BEANS, self::NETHER_WART])){
				$event->setCancelled();
			}
		}
	}

	public function onInventoryPickupItem(\pocketmine\event\inventory\InventoryPickupItemEvent $event){
		if(($inventory = $event->getInventory()) instanceof \pocketmine\inventory\PlayerInventory){
			if(($player = $inventory->getHolder()) instanceof Player){
				if($this->isFarmWorld($player) && !$player->hasPermission("farm.api.pickup.item") && (!$this->isFarm($player) || !$this->isOwner($player, $player) && !$this->data[self::PLAYERS][$this->getOwnerByPosition($player)][self::FARM_PICKUP_ITEM])){
					$event->setCancelled();
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
			$sender->sendMessage(Color::YELLOW . "[FarmAPI] Saved data.");
		}
	}

	public function isFarmWorld(Position $pos){
		return $pos->getLevel()->getFolderName() === "farm";
	}

	public function isFarm(Position $pos){
		return $this->isFarmWorld($pos) && $pos->x >= 0 && $pos->z >= 0 && $pos->x % 110 < 100 && $pos->z % 100 < 100;
	}

	public function giveFarm($player){
		if($player instanceof Player){
			$player = $player->getName();
		}elseif(!is_string($player)){
			return false;
		}
		if(isset($this->data[self::PLAYERS][$player = strtolower($player)])){
			return false;
		}else{
			$this->data[self::FARMS][] = $player;
			$this->data[self::PLAYERS][$player] = [
				self::FARM_LEVEL => 1,
				self::FARM_VISIT_ALLOW => true,
				self::FARM_VISIT_NOTICE => true,
				self::FARM_VISIT_MESSAGE => "[농장] 어서오세요. 이곳은 " . $player . "님의 농장입니다.",
				self::FARM_WATER_FLOW => true,
				self::FARM_PVP_ALLOW => true,
				self::FARM_PICKUP_ITEM => false,
				self::FARM_SPAWN => "50:5:50"
			];
			return true;
		}
	}

	public function getFarmSpawn($player){
		if($player instanceof Player){
			$player = $player->getName();
		}elseif(!is_string($player)){
			return false;
		}
		if(!isset($this->data[self::PLAYERS][$player = strtolower($player)])){
			return false;
		}else{
			$farmNum = array_search($player, $this->data[self::FARMS]);
			$farmX = $farmNum % 30;
			$farmZ = ($rest = $farmNum - $farmX) == 0 ? 0 : $rest / 30;
			$pos = explode(":", $this->data[self::PLAYERS][$player][self::FARM_SPAWN]);
			return $this->getServer()->getLevelByName("farm")->getSafeSpawn(new Vector3($farmX * 110 + $pos[0], $pos[1], $farmZ * 110 + $pos[2]));
		}
	}

	public function getOwnerByPosition(Position $pos){
		if(($index = $this->getFarmIndexByPosition($pos)) !== false && isset($this->data[self::FARMS][$index])){
			return $this->data[self::FARMS][$index];
		}
		return false;
	}

	public function getFarmIndexByPosition(Position $pos){
		if(!$this->isFarm($pos)){
			return false;
		}else{
			$pos = $pos->floor();
			$farmX = ($x = $pos->x - ($pos->x % 110)) == 0 ? 0 : $x / 110;
			$farmZ = ($z = $pos->z - ($pos->z % 110)) == 0 ? 0 : $z / 110;
			if($farmX < 0 || $farmZ < 0 || $farmX >= 30){
				return false;
			}else{
				return $farmX + $farmZ * 30;
			}
		}
	}

	public function isOwner(Position $pos, $player){
		if($player instanceof Player){
			$player = $player->getName();
		}elseif(!is_string($player)){
			return false;
		}
		return $this->isFarm($pos) && strtolower($this->getOwnerByPosition($pos)) == ($player = strtolower($player));
	}

	public function isInFarm(Position $pos, $player){
		if($player instanceof Player){
			$player = $player->getName();
		}elseif(!is_string($player)){
			return false;
		}
		if($this->isFarm($pos) && strtolower($this->getOwnerByPosition($pos)) == ($player = strtolower($player))){
			$pos = $pos->floor();
			$farmNum = array_search($player, $this->data[self::FARMS]);
			$farmX = $farmNum % 30;
			$farmZ = ($rest = $farmNum - $farmX) == 0 ? 0 : $rest / 30;
			$midX = $farmX * 110 + 49;
			$midZ = $farmZ * 110 + 49;
			$dis = $this->getLevel($player) * 5;
			if($pos->x > $midX - $dis && $pos->x <= $midX + $dis && $pos->z > $midZ - $dis && $pos->z <= $midZ + $dis){
				return true;
			}
		}
		return false;
	}

	public function getLevel($player){
		if($player instanceof Player){
			$player = $player->getName();
		}elseif(!is_string($player)){
			return false;
		}
		if(isset($this->data[self::PLAYERS][$player = strtolower($player)])){
			return $this->data[self::PLAYERS][$player][self::FARM_LEVEL];
		}else{
			return false;
		}
	}

	public function getFarmSenter($player){
		if($player instanceof Player){
			$player = $player->getName();
		}elseif(!is_string($player)){
			return false;
		}
		if(isset($this->data[self::PLAYERS][$player = strtolower($player)])){
			$farmNum = array_search($player, $this->data[self::FARMS]);
			$farmX = $farmNum % 30;
			$farmZ = ($rest = $farmNum - $farmX) == 0 ? 0 : $rest / 30;
			$midX = $farmX * 110 + 50;
			$midZ = $farmZ * 110 + 50;
			return new Vector3($midX, 3, $midZ);
		}
		return false;
	}

	public function levelUp($player){
		if($player instanceof Player){
			$player = $player->getName();
		}elseif(!is_string($player)){
			return false;
		}
		if(isset($this->data[self::PLAYERS][$player = strtolower($player)]) && ($farmLevel = $this->data[self::PLAYERS][$player][self::FARM_LEVEL]) < 10){
			$this->data[self::PLAYERS][$player][self::FARM_LEVEL] = ++$farmLevel;
			$farmNum = array_search($player, $this->data[self::FARMS]);
			$farmX = $farmNum % 30;
			$farmZ = ($rest = $farmNum - $farmX) == 0 ? 0 : $rest / 30;
			$midX = $farmX * 110 + 49;
			$midZ = $farmZ * 110 + 49;
			$dis = $farmLevel * 5;
			$level = $this->getServer()->getLevelByName("farm");
 			for($x = $midX - $dis - 3; $x <= $midX + $dis + 3; $x++){
				for($z = $midZ - $dis - 3; $z <= $midZ + $dis + 3; $z++){
					if($x > $midX - $dis && $x <= $midX + $dis && $z > $midZ - $dis && $z <= $midZ + $dis){
						$level->setBiomeColor($x, $z, 146, 188, 88);
						$iDis = ($farmLevel - 1) * 5;
						if(!($x > $midX - $iDis && $x <= $midX + $iDis && $z > $midZ - $iDis && $z <= $midZ + $iDis) && $x >= $midX - $iDis && $x <= $midX + $iDis + 1 && $z >= $midZ - $iDis && $z <= $midZ + $iDis + 1){
							$level->setBlockIdAt($x, 3, $z, 2);
							for($y = 4; $y < 128; $y++){
								$level->setBlockIdAt($x, $y, $z, 0);
							}
						}
					}elseif($x >= $midX - $dis && $x <= $midX + $dis + 1 && $z >= $midZ - $dis && $z <= $midZ + $dis + 1){
						$level->setBiomeColor($x, $z, 58, 100, 0);
						for($y = 3; $y < 128; $y++){
							$level->setBlockIdAt($x, $y, $z, $y % 2 == 0 ? 132 : 400);
						}
					}
				}
			}
 			return true;
		}else{
			return false;
		}
	}

	public function getData(){
		return $this->data;
	}

	public function getMoney($player){
		if(!$this->money){
			return false;
		}else{
			if($player instanceof Player){
				$player = $player->getName();
			}elseif(!is_string($player)){
				return false;
			}
			switch($this->money->getName()){
				case "PocketMoney":
				case "MassiveEconomy":
				case "Money":
					return $this->money->getMoney($player);
				break;
				case "EconomyAPI":
					return $this->money->mymoney($player);
				break;
				default:
					return false;
				break;
			}
		}
	}

	public function giveMoney($player, $money){
		if(!$this->money){
			return false;
		}else{
			if($player instanceof Player){
				$player = $player->getName();
			}elseif(!is_string($player) || !is_numeric($money) || ($money = floor($money)) <= 0){
				return false;
			}
			switch($this->money->getName()){
				case "PocketMoney":
					$this->money->grantMoney($player, $money);
				break;
				case "EconomyAPI":
					$this->money->setMoney($player, $this->money->mymoney($player) + $money);
				break;
				case "MassiveEconomy":
				case "Money":
					$this->money->setMoney($player, $this->money->getMoney($player) + $money);
				break;
				default:
					return false;
				break;
			}
			return true;
		}
	}


	public function loadData(){
		@mkdir($folder = $this->getDataFolder());
		if(!file_exists($path = $folder . "FarmAPI.sl")){	
			file_put_contents($path, serialize([self::FARMS => [], self::PLAYERS => []]));
		}
		$this->data = unserialize(file_get_contents($path));
	}

	public function saveData(){
		@mkdir($folder = $this->getDataFolder());
		file_put_contents($folder . "FarmAPI.sl", serialize($this->data));
	}
}