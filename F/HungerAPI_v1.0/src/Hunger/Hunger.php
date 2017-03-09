<?php
namespace Hunger;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerChatvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\server\RemoteServerCommandEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\network\protocol\PlayerActionPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\entity\Attribute;
use pocketmine\entity\Effect;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;
use Hunger\task\CheckPlayersTask;
use Hunger\block\Cake;

class Hunger extends PluginBase implements Listener{
	const NONE_STAND = 0;
	const NONE_SLEEP = 1;
	const NONE_SNEAK = 2;
	const MOVE_WALK = 3;
	const MOVE_SNEAK = 4;
	const MOVE_SPRINT = 5;
	const MOVE_WATER = 6;
	const MOVE_JUMP = 7;
	const ACTION_BREAK = 8;
	const ACTION_PLACE = 9;
	const ACTION_TOUCH = 10;
	const ACTION_ATTACK = 11;
	const ACTION_CHAT = 12;
	const ACTION_DROP_ITEM = 13;
	const DAMAGE_HUNGER = 16;

	private static $instance = null;
	private $data = [], $checkTick = 0, $hargerAttribute, $foodList;

	public static function getInstance(){
		return self::$instance;
	}

	public static function getHunger($player){
		if(!($plugin = self::getInstance()) instanceof Hunger){
			return false;
		}elseif($player instanceof Player){
			$name = strtolower($player->getName());
			if(!isset($plugin->data[$name])){
				$plugin->data[$name] = 20;
			}
		}elseif(!is_string($player) || !isset($plugin->data[$name = strtolower($player)])){
			return false;
		}
		return $plugin->data[$name];
	}

	public static function setHunger($player, $hunger = 20){
		if(self::getHunger($player) === false){
			return false;
		}elseif($hunger < 0){
			$hunger = 0;
		}elseif($hunger > 20){
			$hunger = 20;
		}
		if($player instanceof Player){
			$name = strtolower($player->getName());
		}elseif(!is_string($player)){
			return false;
		}else{
			$name = strtolower($player);
		}
		self::getInstance()->data[$name] = $hunger;
		if($player instanceof Player){
			self::getInstance()->sendHunger($player);
		}
		return true;
	}

	public static function saturation($player, $amount = 1){
		if(self::getHunger($player) === false || self::getHunger($player) >= 20){
			return false;
		}
		self::setHunger($player, self::getHunger($player) + $amount);
		return true;
	}

	public static function reduce($player, $amount = 1){
		if(self::getHunger($player) === false || self::getHunger($player) <= 0){
			return false;
		}
		self::setHunger($player, self::getHunger($player) - $amount);
		return true;
	}

	public static function getAmount($cause){
		switch($cause){
			case self::NONE_STAND:
				return 0.001;
			case self::NONE_SLEEP:
				return 0.0005;
			case self::NONE_SNEAK:
				return 0.0008;
			case self::MOVE_WALK:
				return 0.003;
			case self::MOVE_SNEAK:
				return 0.0005;
			case self::MOVE_SPRINT:
				return 0.001;
			case self::MOVE_WATER:
				return 0.001;
			case self::MOVE_JUMP:
				return 0.005;
			case self::ACTION_BREAK:
				return 0.01;
			case self::ACTION_PLACE:
				return 0.01;
			case self::ACTION_TOUCH:
				return 0.005;
			case self::ACTION_ATTACK:
				return 0.01;
			case self::ACTION_CHAT:
				return 0.005;
			case self::ACTION_DROP_ITEM:
				return 0.005;
			default:
				return 0;
		}
	}

	public function onLoad(){
		if(self::$instance === null){
			self::$instance = $this;
		}
 		Attribute::addAttribute(3, "player.hunger", 0, 20, 20, true);
 		Attribute::addAttribute(5, "generic.movementSpeed", -100, 24791, 0.1, true);
		$this->hungerAttribute = Attribute::getAttributeByName("player.hunger");
		$this->speedAttribute = Attribute::getAttributeByName("generic.movementSpeed");
		$this->registerBlock(Block::CAKE_BLOCK, Cake::class);
 	}

	public function registerBlock($id, $class){
		Block::$list[$id] = $class;
		for($data = 0; $data < 16; ++$data){
			Block::$fullList[($id << 4) | $data] = new $class($data);
		}		
	}

	public function onEnable(){
		$this->loadData();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new CheckPlayersTask($this), 2);
	}

	public function onDisable(){
		$this->saveData();
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		$ik = $this->isKorean();
		if(!isset($sub[0])){
			$sub[0] = 20;
		}elseif(!is_numeric($sub[0]) || $sub[0] < 0 || $sub[0] > 20){
			$r = Color::RED . "[Feed] $sub[0]" . ($ik ? "은(는) 잘못된 숫자입니다." : " is invalid number");
		}
		if(!$sender instanceof Player){
			if(!isset($sub[1]) || $sub[1] == ""){
				$r = Color::RED . "Usage: /Feed " . ($ik ? "<플레이어명>" : "<PlayerName>");
			}elseif(!($player = $this->getServer()->getPlayer($sub[1])) instanceof Player){
				$r = Color::RED . "[Feed] $sub[1]" . ($ik ? "은(는) 잘못된 플레이어명입니다." : " is invalid player name");
			}
		}elseif(isset($sub[1]) && $sub[1] != ""){
			if(!($player = $this->getServer()->getPlayer($sub[1])) instanceof Player){
				$r = Color::RED . "[Feed] $sub[1]" . ($ik ? "은(는) 잘못된 플레이어명입니다." : " is invalid player name");
			}
		}else{
			$player = $sender;
		}
		self::setHunger($player, $sub[0]);
		$r = Color::YELLOW . "[Feed] " . ($ik ? "배고픔이 " . $sub[0] . "으로 설정되었습니다." : "Hungry is set to $sub[0]");
		$sender->sendMessage($r);
		return true;
	}

	/**
	 * @priority HIGHEST
	 */
	public function onPlayerMove(PlayerMoveEvent $event){
		$player = $event->getPlayer();
		if($player->isSurvival() && !$event->isCancelled() && $event->getFrom()->distance($event->getTo()) > 0){
			if($player->isSneaking()){
				self::reduce($player, self::getAmount(self::MOVE_SNEAK));
			}elseif($player->isSprinting()){
				self::reduce($player, self::getAmount(self::MOVE_SPRINT));
			}else{
				self::reduce($player, self::getAmount(self::MOVE_WALK));				
			}
			if($player->isInsideOfWater()){
				self::reduce($player, self::getAmount(self::MOVE_WATER));
			}
 		}		
	}

	/**
	 * @priority HIGHEST
	 */
	public function onBlockBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		if($player->isSurvival() && !$event->isCancelled()){
			self::reduce($player, self::getAmount(self::ACTION_BREAK));
 		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onBlockPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		if($player->isSurvival() && !$event->isCancelled()){
			self::reduce($player, self::getAmount(self::ACTION_PLACE));
 		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onPlayerInteract(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		if($player->isSurvival() && !$event->isCancelled()){
			self::reduce($player, self::getAmount(self::ACTION_TOUCH));
 		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onPlayerChat(PlayerChatEvent $event){
		$player = $event->getPlayer();
		if($player->isSurvival() && !$event->isCancelled()){
			self::reduce($player, self::getAmount(self::ACTION_CHAT));
 		}
	}

	public function onPlayerJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		if(!isset($plugin->data[$name = strtolower($player->getName())])){
			$plugin->data[$name] = 20;
		}
		$this->sendHunger($player);
	}

	public function onPlayerDeath(PlayerDeathEvent $event){
		$player = $event->getEntity();
		$cause = $player->getLastDamageCause();
 		self::setHunger($player, ($cause === null ? null : $cause->getCause()) === self::DAMAGE_HUNGER ? 20 : self::getHunger($player));
	}

	public function onDataPacketReceiveEvent(DataPacketReceiveEvent $event) {
		$player = $event->getPlayer();
		$packet = $event->getPacket();
		if(($pid = $packet->pid()) == ProtocolInfo::ENTITY_EVENT_PACKET && $packet->event == 9){ // Eating
			if($player->spawned !== false && $player->blocked !== true && $player->isAlive()){
				$item = $player->getInventory()->getItemInHand();
				if(isset($this->foodList[$foodIndex = $item->getID() . ":" . $item->getDamage()]) || isset($this->foodList[$foodIndex = $item->getID() . ":?"])){
					$event->setCancelled();
					$player->craftingType = 0;
					$player->setDataFlag(Player::DATA_FLAGS, Player::DATA_FLAG_ACTION, false); // TODO: check if this should be true
					$this->getServer()->getPluginManager()->callEvent($ev = new PlayerItemConsumeEvent($player, $item));
					if($ev->isCancelled()){
						$player->getInventory()->sendContents($player);
						return;
					}
					$pk = new EntityEventPacket();
					$pk->eid = $player->getId();
					$pk->event = EntityEventPacket::USE_ITEM;
					$player->dataPacket($pk);
					$this->getServer()->broadcastPacket($player->getViewers(), $pk);
					self::saturation($player, $this->foodList[$foodIndex]);
					--$item->count;
					$player->getInventory()->setItemInHand($item, $player);
					if(in_array($item->getID(), [Item::MUSHROOM_STEW, Item::BEETROOT_SOUP, 413])){ // 413 : Rabbit Stew
						$player->getInventory()->addItem(Item::get(Item::BOWL, 0, 1));
					}else{
						switch($foodIndex){
							case "322:0": // Golden Apple : Regeneration[II,5], Absorption[I,120]
								$player->addEffect(Effect::getEffect(Effect::REGENERATION)->setAmplifier(1)->setDuration(5 * 20));
							//	$player->addEffect(Effect::getEffect(Effect::ABSORPTION)->setAmplifier(0)->setDuration(120 * 20));
							break;
							case "322:1": // Enchanted Golden Apple : Regeneration[V,30], Absorption[I,120], Resistance[I,300], FireResistanc[I,300]
								$player->addEffect(Effect::getEffect(Effect::REGENERATION)->setAmplifier(4)->setDuration(30 * 20));
							//	$player->addEffect(Effect::getEffect(Effect::ABSORPTION)->setAmplifier(0)->setDuration(120 * 20));
								$player->addEffect(Effect::getEffect(Effect::DAMAGE_RESISTANCE)->setAmplifier(0)->setDuration(300 * 20));
								$player->addEffect(Effect::getEffect(Effect::FIRE_RESISTANCE)->setAmplifier(0)->setDuration(300 * 20));
							break;
							case "349:3": // Pufferfish : Nausea[II,15], Poison[IV,60], Hunger[III,15]
								$player->addEffect(Effect::getEffect(Effect::NAUSEA)->setAmplifier(1)->setDuration(15 * 20));
								$player->addEffect(Effect::getEffect(Effect::POISON)->setAmplifier(3)->setDuration(60 * 20));
							//	$player->addEffect(Effect::getEffect(Effect::HUNGER)->setAmplifier(2)->setDuration(15 * 20));
							break;
							case "367:?": // Rotten Flesh : Hunger[I,30]
							//	$player->addEffect(Effect::getEffect(Effect::HUNGER)->setAmplifier(0)->setDuration(30 * 20));
							break;
							case "375:?": // Spider Eye : Position[I,4]
								$player->addEffect(Effect::getEffect(Effect::POISON)->setAmplifier(0)->setDuration(4 * 20));
							break;
							case "325:1": // Milk : Clear Effects
								$player->removeAllEffects();
								$player->getInventory()->setItemInHand(Item::get(Item::BUCKET, 0, 1));
							break;
						}
					}
				}
			}
		}elseif($pid == ProtocolInfo::PLAYER_ACTION_PACKET){
			if($packet->action == PlayerActionPacket::ACTION_JUMP){
				self::reduce($player, self::getAmount(self::MOVE_WATER));
			}elseif($packet->action == PlayerActionPacket::ACTION_START_SPRINT && self::getHunger($player) < 6){
				$event->setCancelled();
			}
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

	public function checkSaveAll(\pocketmine\command\CommandSender $sender){
		if(($command = $this->getServer()->getCommandMap()->getCommand("save-all")) instanceof Command && $command->testPermissionSilent($sender)){
			$this->saveData();
			$sender->sendMessage(Color::YELLOW . "[Hunger] Saved data.");
		}
	}

	public function checkPlayers(){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			if($player->isSurvival()){
				if($player->isSleeping()){
					self::reduce($player, self::getAmount(self::NONE_SLEEP));
				}elseif($player->isSneaking()){
					self::reduce($player, self::getAmount(self::NONE_SNEAK));
				}else{
					self::reduce($player, self::getAmount(self::NONE_STAND));				
				}
				$this->checkTick++;
				if($this->checkTick > 10){
					$this->checkTick = 0;
					if(($hunger = ceil(self::getHunger($player))) == 20 && $player->getMaxHealth() > $player->getHealth()){
						$player->heal(1, new EntityRegainHealthEvent($player, 1, EntityRegainHealthEvent::CAUSE_MAGIC));
					}elseif($hunger == 0 && $player->getHealth() >= 1){
						$player->attack(1, new EntityDamageEvent($player, self::DAMAGE_HUNGER, 1));
					}
		 		}
		 	}		
		}
	}

	public function sendHunger(Player $player){
		$packet = new UpdateAttributesPacket();
		$packet->entityId = 0;
 		$packet->entries = [clone $this->hungerAttribute->setValue(ceil(self::getHunger($player)))];
		$player->dataPacket($packet);
/*
		$packet = new UpdateAttributesPacket();
		$packet->entityId = 0;
 		$packet->entries = [clone $this->speedAttribute->setValue(-0.3)];
		$player->dataPacket($packet);
*/
	}

	public function loadData(){
		@mkdir($folder = $this->getDataFolder());
		if(!file_exists($path = $folder . "Hungers.sl")){	
			file_put_contents($path, serialize([]));
		}
		$this->data = unserialize(file_get_contents($path));
/*
 * Food List from : http://minecraft.gamepedia.com/Food#Foods
 */
		$this->foodList = (new Config($folder . "FoodList.yml", Config::YAML, [
			"260:?" => 4, //Apple
			"393:?" => 6, // Baked Potato
			"457:?" => 1, //Beetroot
			"459:?" => 10, //Beetroot Soup
			"297:?" => 5, // Bread
		//"0:92 => 2, // Cake - Slice : 2, Whole : 14
			"318:?" => 3, // Carrot
		//	"432:?" => 4, // Chorus Fruit
			"349:2" => 1, // Clownfish
			"366:?" => 6, // Cooked Chicken
			"350:0" => 5, // Cooked Fish
		//	"424:?" => 6, // Cooked Mutton
			"320:?" => 8, // Cooked Porkchop
			"412:?" => 5, // Cooked Rabbit
			"350:1" => 6, // Cooked Salmon
			"357:9" => 2, // Cookie
			"322:0" => 4, // Golden Apple : Regeneration[II,5], Absorption[I,120]
			"322:1" => 4, // Enchanted Golden Apple : Regeneration[V,30], Absorption[I,120], Resistance[I,300], FireResistanc[I,300] 
			"396:?" => 6, // Golden Carrot
			"360:?" => 2, // Melon
			"282:?" => 6, //Mushroom Stew
			"392:?" => 1, // Potato
			"349:3" => 1, // Pufferfish : Nausea[II,15], Poison[IV,60], Hunger[III,15]
			"400:?" => 8, // Pupkin Pie
			"413:?" => 10, // Rabbit Stew
			"363:?" => 3, // Raw Beef
			"365:?" => 2, // Raw Chicken
			"349:0" => 2, // Raw Fish
		//	"423:?" => 2, // Raw Mutton
			"319:?" => 3, // Raw Porkchop
			"411:?" => 3, // Raw Rabbit
			"349:1" => 2, // Raw Salmon
			"367:?" => 4, // Rotten Flesh : Hunger[I,30]
			"375:?" => 2, // Spider Eye : Position[I,4]
 			"364:0" => 8, // Steak
 			"325:1" => 0  // Milk : Clear Effects
		]))->getAll();
	}

	public function saveData(){
		@mkdir($folder = $this->getDataFolder());
		file_put_contents($folder . "Hungers.sl", serialize($this->data));
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}