<?php

namespace RubyGame;

use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
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
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class RubyGame extends PluginBase implements Listener{
	private static $instance = null;
	private $games = [];

	public static function getInstance(){
		return self::$instance;
	}

	public function onLoad(){
/*		$game = new TestGame();
		$level = $this->getServer()->getDefaultLevel();
		$game->setLobbyPos(new Position(0, 4, 0, $level));
		$game->setStartPos(new Position(4, 4, 4, $level));
		$game->setMinPos(new Position(-10, 4, -10, $level));
		$game->setMaxPos(new Position(6, 4, 6, $level));
		$this->registerGame($game);
*/	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDisable(){
	}

	public function onPlayerItemHeld(PlayerItemHeldEvent $event){
	}

	public function onBlockBreak(BlockBreakEvent $event){
	}

	public function onBlockPlace(BlockPlaceEvent $event){
	}

	public function onPlayerInteract(PlayerInteractEvent $event){
	}

	public function onPlayerDropItem(PlayerDropItemEvent $event){
		$event->setCancelled();
	}

	public function onPlayerRespawn(PlayerRespawnEvent $event){
	}

	public function onPlayerJoin(PlayerJoinEvent $event){
		
	}

	public function onPlayerQuit(PlayerQuitEvent $event){
	}

	public function onPlayerDeath(PlayerDeathEvent $event){
	}

	public function onPlayerMove(PlayerMoveEvent $event){
	}

	public function onEntityDamage(EntityDamageEvent $event){
	}

	public function registerGame(BaseGame $game){
		$this->games[] = $game;
	}

	public function scheduleDelayedRepeatingTask($class, string $function, int $delay, int $period, array $array = []){
		$this->scheduleList[] =  $this->getServer()->getScheduler()->scheduleDelayedRepeating(new Task($this, [$class, $function], $array), $delay, $period)->getTaskId();
	}

	public function scheduleDelayedTask($class, string $function, int $delay, array $array = []){
		$this->getServer()->getScheduler()->scheduleDelayed(new Task($this, [$class, $function], $array), $delay);
	}

	public function scheduleRepeatingTask($class, string $function, int $period, array $array = []){
		$this->scheduleList[] =  $this->getServer()->getScheduler()->scheduleRepeating(new Task($this, [$class, $function], $array), $period)->getTaskId();
	}
}