<?php

namespace AbilitySurvival;

use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\entity\Human;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\math\AxisAlignedBB as AABB;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;
use pocketmine\entity\Effect;

class AbilitySurvival extends PluginBase implements Listener{
	const STOP = 0;
	const WAIT = 1;
	const START = 2;

	const STOP_PLAYER_QUIT = 0;
	const STOP_WIN = 1;
	const STOP_SERVER_STOP = 2;
	const STOP_ERROR = 3;

	const PLAYER_MIN = 3;
	const PLAYER_MAX = 20;
	const WAIT_MAX = 30;

	const PLAYER = 0;
	const LIFE = 1;
	const ABILITY = 2;
	const COOL = 3;

	protected $startInfo = self::STOP;
	protected $players = []; //Player Player, Int Life, Int Ability, Int Cool 
	protected $tasks = [];
	protected $positions = ["Lobby" => false, "Start" => false, "Min" => false, "Max" => false];
	protected $tick = 0;
	protected $waitTick = self::WAIT_MAX;

	public function onEnable(){
		$this->startInfo = self::STOP;
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this, "onTick"]), 5);
	}

	public function onTick(){
		if(($count = count($players = $this->getServer()->getOnlinePlayers())) >= self::PLAYER_MIN && $this->startInfo == self::STOP){
			$this->startInfo = self::WAIT;
		}elseif($count < self::PLAYER_MIN && $this->startInfo !== self::STOP){
			if($this->startInfo == self::WAIT){
				$this->startInfo == self::STOP;
			}else{
				$this->gameStop(self::STOP_PLAYER_QUIT);
			}
		}elseif($count >= self::PLAYER_MAX && $this->startInfo == self::WAIT){
			$this->startGame();
		}
		$dead = 0;
		foreach($this->players as $info) if($info[self::LIFE] <= 0) $dead++;
		$countMessage = Color::RESET."\n".Color::BOLD.Color::DARK_GREEN."[AbilitySurvival] Players: ".Color::GREEN."[".str_pad($count, strlen(self::PLAYER_MAX), "0", STR_PAD_LEFT)."/".self::PLAYER_MAX."] ";
		foreach($players as $player){
			switch($this->startInfo){
				case self::STOP:
					$player->sendPopup(Color::YELLOW."Please wait for ".Color::GOLD."Another Players".Color::RESET.Color::GOLD."...".["-", "\\", ".|", "/"][$this->tick].$countMessage);
				break;
				case self::WAIT:
					$player->sendPopup(Color::BOLD.Color::YELLOW."It's time to play ".Color::GOLD."AbilitySurvival...  ".Color::AQUA.str_pad($this->waitTick, strlen(self::WAIT_MAX), "0", STR_PAD_LEFT).Color::DARK_AQUA."sec".$countMessage);
				break;
				case self::START:
					$info = isset($this->players[$name]) ? $this->players[$name] : $this->players[$name] = [self::PLAYER => $player, self::LIFE => 0, self::ABILITY => Ability::NORMAL, self::COOL => 0];
					$abilityMessage = str_repeat(" ", 20);
					switch($this->players[$name = $player->getName()][self::ABILITY]){
						case Ability::NORMAL:
							$abilityMessage .= "";
						break;
					}
					$player->sendPopup(Color::BOLD.Color::YELLOW."Ability: ".Color::GOLD.Ability::$names[$info[self::ABILITY]].Color::DARK_RED."  Life: ".Color::RED.$info[self::LIFE].Color::DARK_AQUA."  Cool: ".Color::AQUA.$info[self::COOL].$countMessage.Color::GRAY." Dead: ".Color::WHITE.$dead.$abilityMessage);
				break;
			}
		}
		$this->tick++;
		if($this->tick >= 4) $this->tick = 0;
		if($this->tick == 0){
			if($this->startInfo !== self::WAIT) $this->waitTick = self::WAIT_MAX;
			else{
				if($this->waitTick <= 0) $this->gameStart(); 
				else $this->waitTick--;
			}
			foreach($this->players as $name => $info){
				if(($cool = $info[self::COOL]) > 0){
					$this->players[$name][self::COOL] = floor(max(0, $cool = $cool - 1));
				}
			}
		}
	}

	public function gameStart(){
		foreach($this->players as $info){
			$this->cleanPlayer($info[self::PLAYER]);
		}
		$abilities = [];
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$this->players[$name = $player->getName()] = [self::PLAYER => $player, self::LIFE => 1, self::ABILITY => Ability::NORMAL, self::COOL => 0];
			if(count($abilities) == 0){
 				$abilities = array_keys(Ability::$names);
 				shuffle($abilities);
 			} 
 			$this->setAbility($player, array_shift($abilities));
 		}
		$this->startInfo = self::START;
	}

	public function setAbility(Player $player, int $ability){
		$this->players[$player->getName()][self::ABILITY] = $ability;
		switch($ability){
			case Ability::NORMAL:
			break;
		}
	}

	public function gameStop($cause = self::STOP_ERROR){
		foreach($this->players as $info){
			$this->cleanPlayer($info[self::PLAYER]);
		}
		$this->startInfo = self::STOP;
	}

	public function cleanPlayer(Player $player, $dead = false){
		$player->extinguish();
		$player->getInventory()->clearAll();
		$player->getInventory()->sendContents($player);
		$player->removeAllEffects();
		$player->setAllowFlight(false); 
		if($dead){
			$player->setGamemode(2);
			$player->addEffect(Effect::getEffect(1)->setAmplifier(4)->setDuration(100000)->setVisible(false)); //신속
			$player->addEffect(Effect::getEffect(4)->setAmplifier(255)->setDuration(100000)->setVisible(false)); //피로
			$player->addEffect(Effect::getEffect(8)->setAmplifier(4)->setDuration(100000)->setVisible(false)); //점프강화
			$player->addEffect(Effect::getEffect(13)->setAmplifier(4)->setDuration(100000)->setVisible(false)); //수중호흡
			$player->addEffect(Effect::getEffect(14)->setDuration(100000)->setVisible(false)); //투명화
			$player->addEffect(Effect::getEffect(21)->setAmplifier(-4)->setDuration(100000)->setVisible(false)); //체력신장 -로해서 최대체력4(하트2)
	 		$player->setMaxHealth(4);
			$player->setHealth(4);
		}else{
 			if($player->getGamemode() !== 0) $player->setGamemode(0);
			$player->setMaxHealth(20);
			$player->setHealth(20);
		}
	}

	public function onPlayerJoin(PlayerJoinEvent $event){
		$event->setJoinMessage(Color::GREEN."[AS] ".Color::BOLD.Color::ITALIC.$event->getPlayer()->getName().Color::RESET.Color::YELLOW." is Join the this server ".Color::BOLD.Color::ITALIC.Color::DARK_GREEN."[".Color::GREEN.count($this->getServer()->getOnlinePlayers()).Color::DARK_GREEN."/".Color::GREEN.self::PLAYER_MAX.Color::DARK_GREEN."]");
	}

	public function onPlayerQuit(PlayerQuitEvent $event){
		$event->setQuitMessage(Color::RED."[AS] ".Color::BOLD.Color::ITALIC.$event->getPlayer()->getName().Color::RESET.Color::YELLOW." is Join the this server ".Color::BOLD.Color::ITALIC.Color::DARK_RED."[".Color::RED.count($this->getServer()->getOnlinePlayers()).Color::DARK_RED."/".Color::RED.self::PLAYER_MAX.Color::DARK_RED."]");
	}

	public function onPlayerRespawn(PlayerRespawnEvent $event){
		$event->setRespawnPosition($event->getPlayer());
 	}

	public function onPlayerDeath(PlayerDeathEvent $event){
		$event->setDeathMessage("");
		$event->setKeepInventory(true);
		$player = $event->getEntity();
	}

	public function onPlayerInteract(PlayerInteractEvent $event){
		if(!$event->getPlayer()->isOp()) $event->setCancelled();
	}

	public function onBlockBreak(BlockBreakEvent $event){
		if($this->startInfo !== self::START && !$event->getPlayer()->isOp()) $event->setCancelled();
	}

	public function onBlockPlace(BlockPlaceEvent $event){
		if($this->startInfo !== self::START && !$event->getPlayer()->isOp()) $event->setCancelled();
 	}

	public function onPlayerDropItem(PlayerDropItemEvent $event){
		if($this->startInfo !== self::START && !$event->getPlayer()->isOp()) $event->setCancelled();
	}

	public function onPlayerMove(PlayerMoveEvent $event){
	}

	public function onEntityDamage(EntityDamageEvent $event){
		if(($player = $event->getEntity()) instanceof Player){
			if($event instanceof EntityDamageByEntityEvent){
				$damager = $event->getDamager();
			}
		}
	}

	public function addSchedule($class = false, $function = false, $time = 0, $array = []){
		if(!$class || !$function || !is_array($array) || !is_numeric($time)) return false;
		$task = $this->getServer()->getScheduler()->scheduleDelayedTask(new Task($this, [$class, $function], $array), $time);
		$this->scheduleList[] = $task->getTaskId();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}

class Ability{
	const NORMAL = 0;
	const CHICKEN = 1;
		/* 닭
			낙하 데미지를 받지않음.
			점프강화(2)에 걸림 */
	const PIG = 2;
		/* 돼지
			음식으로 체력회복시 3배로 회복함 */
	const COW = 3;
		/* 소
			일정시간마다 우유를 얻음
			우유를 먹으면 독(2,3초)과 멀미(1,10초) 혹은 재생(2,3초)과 체력신장(1,10초)에 걸림 */
	const ZOMBIE = 4;
		/* 좀비
			목숨이 3개임
			주먹으로 상대를 때릴경우 상대에게 독(1,5초)를 줌 */
	const SPIDER = 5;
		/* 거미
			거미줄로 상대를 때릴경우 상대에게 구속(5, 3초)를 줌 */
	const BLAZE = 3; 
		/* 블레이즈
			용암,불 데미지를 받지않음
			막대기로 때린 상대에게 불(10초)을 붙임 */
	const ENDERMAN = 4;
		/* 엔더맨
			눈덩이를 던지면 눈덩이가 도착한곳으로 텔레포트
			텔레포트할때마다 낙하데미지(2)를 받음 */
	const SLIME = 5;
		/* 슬라임
			목숨이 3개임
			죽을때마다 최대 체력이 절반으로 줄어듬 */
	const GHAST = 6;
		/* 가스트
			철괴로 터치시 바라보는 방향으로 폭발하는 불화살을 쏨 */
	const SQUID = 7;
		/* 오징어
			물속에서 신속(3)와 점프강화(3), 수중호흡(4)에 걸림
			타격을 받으면 먹물을 뿌리고, 신속(2,1초)에 걸림 */
	const WOLF = 8;
		/* 늑대
			신속(2)와 점프강화(2), 재생(1), 체력신장(2)에 걸림
			주먹의 공격력이 8배 강해짐
		*/
	const BAT = 9;
		/* 박쥐
			철괴로 터치하면 잠시동안 하늘을 날수있음
			낙하데미지를 받지 않음 */
	const CREEPER = 10;
		/* 크리퍼
			죽을때 강력하게 폭발함 */
	public static $names = [
		self::NORMAL => "Normal",
		self::CHICKEN => "Chiken",
		self::PIG => "Pig",
		self::COW => "Cow",
		self::ZOMBIE => "Zombie",
		self::SPIDER => "Spider",
		self::BLAZE => "Blaze",
		self::ENDERMAN => "Enderman",
		self::SLIME => "Slime",
		self::GHAST => "Ghast",
		self::SQUID => "Squid",
		self::WOLF => "Wolf",
		self::BAT => "Bat",
		self::CREEPER => "Creeper",
	];
}