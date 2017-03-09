<?php

namespace FarmBooster;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\level\format\FullChunk;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat as Color;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\server\RemoteServerCommandEvent;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use FarmBooster\task\DoChunksTickTask;
use FarmBooster\task\DoChunksTickAsyncTask;
use FarmBooster\task\CheckBoostersTask;

class FarmBooster extends PluginBase implements Listener{ 
	const NETHER_WART_BLOCK = 115;
	const NETHER_WART_SEEDS = 372;
	const COCOA_BEANS_BLOCK = 127;

	public $levelTickBlocks = [], $boosters = [], $spawned = [], $eids = [];
	public $randomTickBlocks = [
	 	Block::WHEAT_BLOCK,
		Block::BEETROOT_BLOCK,
		Block::CARROT_BLOCK,
		Block::POTATO_BLOCK,
		Block::CACTUS,
		Block::SUGARCANE_BLOCK,
		Block::MELON_STEM,
		Block::PUMPKIN_STEM,
	 	self::COCOA_BEANS_BLOCK,
		self::NETHER_WART_BLOCK
	];
	public $maxDamage = [
	 	Block::WHEAT_BLOCK => 0x07,
		Block::BEETROOT_BLOCK => 0x07,
		Block::CARROT_BLOCK => 0x07,
		Block::POTATO_BLOCK => 0x07,
	 	self::COCOA_BEANS_BLOCK => 0xA1,
		self::NETHER_WART_BLOCK => 0x04
	];

	public function onEnable(){
		$this->loadData();
		$logger = $this->getServer()->getLogger();
		$pluginManager = $this->getServer()->getPluginManager();
		$logger->info(Color::GREEN . "Find economy plugin...");
		if(!($this->money = $pluginManager->getPlugin("PocketMoney")) && !($this->money = $pluginManager->getPlugin("EconomyAPI")) && !($this->money = $pluginManager->getPlugin("MassiveEconomy")) && !($this->money = $pluginManager->getPlugin("Money"))){
			$logger->info(Color::RED . "[FarmBooster] " . ($this->isKorean() ? "경제 플러그인을 찾지 못했습니다." : "Failed find economy plugin..."));
			$this->setEnabled(false);
		}elseif(!($this->farm = $pluginManager->getPlugin("FarmAPI"))){
			$logger->info(Color::RED . "[FarmBooster] " . ($this->isKorean() ? "농장 플러그인을 찾지 못했습니다." : "Failed find farm plugin..."));
			$this->setEnabled(false);
		}else{
			$logger->info(Color::GREEN . "[FarmBooster] " . ($this->isKorean() ? "경제 플러그인을 찾았습니다. : " : "Finded economy plugin : ") . $this->money->getName());
			$logger->info(Color::GREEN . "[FarmBooster] " . ($this->isKorean() ? "농장 플러그인을 찾았습니다. : " : "Finded farm plugin "));
			$pluginManager->registerEvents($this, $this);
			$this->getServer()->getScheduler()->scheduleRepeatingTask(new DoChunksTickTask($this), 20);
			$this->getServer()->getScheduler()->scheduleRepeatingTask(new CheckBoostersTask($this), 20);
		}
	}

	public function onDisable(){
		$this->saveData();
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		if(!isset($sub[0]) || $sub[0] == ""){
			$sender->sendMessage(Color::RED . "[농장부스터] 사용법 : /부스터 <시간>(분)");	
			$sender->sendMessage(Color::RED . "[농장부스터] 가격 : 1분당 2,000$");		
			$sender->sendMessage(Color::DARK_RED . "[농장부스터] 시간은 분단위입니다.");
		}elseif(!$sender instanceof Player){
			$sender->sendMessage(Color::RED . "[농장부스터] 게임내에서만 사용해주세요.");
		}elseif(!is_numeric($sub[0])){
			$sender->sendMessage(Color::RED . "[농장부스터] " . $sub[0] . "는 잘못된 숫자입니다.");
		}elseif(($sub[0] = floor($sub[0])) <= 0){
			$sender->sendMessage(Color::RED . "[농장부스터] " . $sub[0] . "너무 작은 숫자입니다.");
		}else{
			$price = $sub[0] * 2000;
			$money = $this->getMoney($sender);
			if($money < $price){
				$sender->sendMessage(Color::RED . "[농장부스터] 돈이 " . $price . "보다 적습니다.");
			}else{
				$sub[0] *= 60;
				$this->giveMoney($sender, -$price);
				if(!isset($this->boosters[$name = strtolower($sender->getName())])){
					$this->boosters[$name] = $sub[0];
				}else{
					$this->boosters[$name] += $sub[0];
				}
				$sender->sendMessage(Color::AQUA . "[농장부스터] 부스터 " . date("H시간 i분", $sub[0]) . "을 " . $price . "\$ 에 구매하셨습니다.");
				$sender->sendMessage(Color::AQUA . "[농장부스터] 남은 부스터 : " . date("H시간 i분 s초", $this->boosters[$name]));
				$sender->sendMessage(Color::AQUA . "[농장부스터] 남은 돈 : " . ($money - $price) . "$");
			}
		}
		return true;
	}

	/**
	 * @priority HIGHEST
	 */
	public function onBlockPlace(BlockPlaceEvent $event){
		if(!$event->isCancelled()){
			$block = $event->getBlock();
			if($this->farm->isFarm($block) &&
				in_array($block->getID(), $this->randomTickBlocks) && 
				!isset($this->levelTickBlocks[$posKey = $block->x . ":" . $block->y . ":" . $block->z]) &&
				$this->farm->isInFarm($block, $ownerName = $this->farm->getOwnerByPosition($block))
			){
				$this->levelTickBlocks[$posKey] = new Vector3($block->x, $block->y, $block->z);
			}
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onBlockUpdate(BlockUpdateEvent $event){
		if(!$event->isCancelled()){
			$block = $event->getBlock();
			if($this->farm->isFarm($block) &&
				in_array($block->getID(), $this->randomTickBlocks) && 
				!isset($this->levelTickBlocks[$posKey = $block->x . ":" . $block->y . ":" . $block->z]) &&
				$this->farm->isInFarm($block, $ownerName = $this->farm->getOwnerByPosition($block))
			){
				$this->levelTickBlocks[$posKey] = new Vector3($block->x, $block->y, $block->z);
			}
		}
	}

	public function onPlayerInteract(PlayerInteractEvent $event){
		$block = $event->getBlock();
		if($this->farm->isFarm($block) &&
			in_array($block->getID(), $this->randomTickBlocks) && 
			!isset($this->levelTickBlocks[$posKey = $block->x . ":" . $block->y . ":" . $block->z]) &&
			$this->farm->isInFarm($block, $ownerName = $this->farm->getOwnerByPosition($block))
		){
			$this->levelTickBlocks[$posKey] = new Vector3($block->x, $block->y, $block->z);
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onServerCommandProcess(ServerCommandEvent $event){
		if(!$event->isCancelled() && stripos("save-all", $command = $event->getCommand()) === 0){
			$this->checkSaveAll($event->getSender());
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onRemoteServerCommand(RemoteServerCommandEvent $event){
		if(!$event->isCancelled() && stripos("save-all", $command = $event->getCommand()) === 0){
			$this->checkSaveAll($event->getSender());
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event){
		if(!$event->isCancelled() && stripos("/save-all", $command = $event->getMessage()) === 0){
			$this->checkSaveAll($event->getPlayer());
		}
	}

	public function checkSaveAll(CommandSender $sender){
		if(($command =  $this->getServer()->getCommandMap()->getCommand("save-all")) instanceof Command && $command->testPermissionSilent($sender)){
			$this->saveData();
			$sender->sendMessage(Color::YELLOW . "[FarmBooster] Saved levelTickBlocks.");
		}
	}

	public function doChunksTick(){
		$this->getServer()->getScheduler()->scheduleAsyncTask(
			new DoChunksTickAsyncTask($this->boosters, $this->levelTickBlocks, $this->farm->getData())
		);
/*
		$level = $this->getServer()->getLevelByName("farm");
		$keys = array_keys($this->levelTickBlocks);

		for($i = mt_rand(0, 5); $i < count($keys); $i += mt_rand(1, 6)){
			$vec = $this->levelTickBlocks[$posKey = $keys[$i]];
			$ownerName = $this->farm->getOwnerByPosition(new Position($vec->x, $vec->y, $vec->z, $level));
			if(isset($this->boosters[$ownerName])){
				$chunk = $level->getChunk($vec->x >> 4, $vec->z >> 4, true);
				if($chunk instanceof FullChunk){
					if(!$chunk->isGenerated()){
						$chunk->setGenerated(true);
					}
					if(!$chunk->isPopulated()){
						$chunk->setPopulated(true);
					}
				}
				$block = $level->getBlock($vec);
	 			if($this->farm->isFarm($block) &&
					in_array($id = $block->getID(), $this->randomTickBlocks) && 
					$this->farm->isInFarm($block, $ownerName)
				){
					$block->onUpdate(Level::BLOCK_UPDATE_RANDOM);
					if(isset($this->maxDamage[$id]) && $block->getDamage() >= $this->maxDamage[$id]){
						unset($this->levelTickBlocks[$posKey]);
					}
				}else{
					unset($this->levelTickBlocks[$posKey]);
				}
			}
		}
*/
	}

	public function doChunksTickCallback($onUpdateBlocks){
		$level = $this->getServer()->getLevelByName("farm");
		$blocks = (array) $onUpdateBlocks;
		foreach($blocks as $posKey => $vec){
			$chunk = $level->getChunk($vec->x >> 4, $vec->z >> 4, true);
			if($chunk instanceof FullChunk){
				if(!$chunk->isGenerated()){
					$chunk->setGenerated(true);
				}
				if(!$chunk->isPopulated()){
					$chunk->setPopulated(true);
				}
			}
			$block = $level->getBlock($vec);
			$ownerName = $this->farm->getOwnerByPosition(new Position($vec->x, $vec->y, $vec->z, $level));
	 		if($this->farm->isFarm($block) &&
				in_array($id = $block->getID(), $this->randomTickBlocks) && 
				$this->farm->isInFarm($block, $ownerName)
			){
				$block->onUpdate(Level::BLOCK_UPDATE_RANDOM);
				if(isset($this->maxDamage[$id]) && $block->getDamage() >= $this->maxDamage[$id]){
					unset($this->levelTickBlocks[$posKey]);
				}
			}else{
				unset($this->levelTickBlocks[$posKey]);
			}
		}
	}

	public function checkBoosters(){
		$level = $this->getServer()->getLevelByName("farm");
		foreach($this->boosters as $name => $time){
			$vec = $this->farm->getFarmSenter($name);
			if(!isset($this->spawned[$name])){
				$this->spawned[$name] = [];
			}
			if(!isset($this->eids[$name])){
				$this->eids[$name] = Entity::$entityCount++;
			}
			if($time <= 0){
				$removeEntityPk = new RemoveEntityPacket();
				$removeEntityPk->eid = $this->eids[$name];
				foreach($this->spawned[$name] as $spawned){
					$spawned[0]->dataPacket($removeEntityPk);
				}
				unset($this->spawned[$name]);
				unset($this->boosters[$name]);
			}else{
				$addEntityPk = new AddEntityPacket();
				$addEntityPk->type = 69;
				$addEntityPk->eid = $this->eids[$name];
				$addEntityPk->x = $vec->x;
				$addEntityPk->y = $vec->y;
				$addEntityPk->z = $vec->z;
				$addEntityPk->speedX = 0;
				$addEntityPk->speedY = 0;
				$addEntityPk->speedZ = 0;
				$addEntityPk->yaw = 0;
				$addEntityPk->pitch = 0;
				$addEntityPk->metadata = [
					Entity::DATA_SHOW_NAMETAG => [Entity::DATA_TYPE_BYTE, true],
				 	Entity::DATA_SILENT => [Entity::DATA_TYPE_BYTE, true],
					Entity::DATA_NO_AI => [Entity::DATA_TYPE_BYTE, true]
				];
				$removeEntityPk = new RemoveEntityPacket();
				$removeEntityPk->eid = $this->eids[$name];
				$setEntityDataPk = new SetEntityDataPacket();
				$setEntityDataPk->eid = $this->eids[$name];
				$setEntityDataPk->metadata = [
					Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, 
						Color::GREEN . "[Farm Booster]\n Time : " . date("H:i:s", $this->boosters[$name])
					]
				];
				foreach($this->getServer()->getOnlinePlayers() as $player){
					if(!isset($this->spawned[$name][$playerName = $player->getName()])){
						if($player->spawned && $player->getLevel() === $level && $vec->distance($player) < 50){
							$player->dataPacket($addEntityPk);
							$this->spawned[$name][$playerName] = [$player, false];
						}
					}else{
						if($player->spawned && $player->getLevel() === $level && $vec->distance($player) < 50){
							$player->dataPacket($setEntityDataPk);
						}else{
							$player->dataPacket($removeEntityPk);
							unset($this->spawned[$name][$playerName]);
						}
					}
				}
				$this->boosters[$name]--;
			}
		}
	}

	public function loadData(){
		@mkdir($folder = $this->getDataFolder());
		if(!file_exists($path = $folder . "Boosters.sl")){	
			file_put_contents($path, serialize([]));
		}
		$this->boosters = unserialize(file_get_contents($path));
		if(!$this->getServer()->isLevelLoaded("farm") && $this->getServer()->isLevelGenerated("farm")){
			$this->getServer()->loadLevel("farm");
		}
		$level = $this->getServer()->getLevelByName("farm");
		@mkdir($folder = $level->getProvider()->getPath());
		if(!file_exists($path = $folder . "FarmBoosterBlocks.sl")){	
			file_put_contents($path, serialize([]));
		}
		foreach(unserialize(file_get_contents($path)) as $posKey){
			$this->levelTickBlocks[$posKey] = new Vector3(...explode(":", $posKey));
		}
	}

	public function saveData(){
		@mkdir($folder = $this->getDataFolder());
		file_put_contents($folder . "Boosters.sl", serialize($this->boosters));
		$level = $this->getServer()->getLevelByName("farm");
		@mkdir($folder = $level->getProvider()->getPath());
		$blocks = [];
		foreach($this->levelTickBlocks as $key => $pos){
			$blocks[] = $pos->x . ":" . $pos->y . ":" . $pos->z;
		}
		file_put_contents($folder . "FarmBoosterBlocks.sl", serialize($blocks));
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

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}