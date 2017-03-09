<?php

namespace MineBlock\RespawnTime;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\nbt\tag\Byte;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Float;
use pocketmine\nbt\tag\Int;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\RespawnPacket;
use pocketmine\network\protocol\SetHealthPacket;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class RespawnTime extends PluginBase implements Listener{
	public function onEnable(){
		$this->player = [];
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		if(!isset($sub[0]) || !is_numeric($sub[0]) || $sub[0] < 0) return false;
		$ik = $this->isKorean();
		$rt = $this->rt;
		$sub[0] = floor($sub[0]);
		$rt["Time"] = $sub[0];
		$sender->sendMessage("[RespawnTime] " . ($ik ? "부활시간을 $sub[0]로 설정했습니다." : "Set respawn time to $sub[0]"));
		if($this->rt !== $rt){
			$this->rt = $rt;
			$this->saveYml();
		}
		return true;
	}

	public function onPlayerJoin(PlayerJoinEvent $event){
		$p = $event->getPlayer();
		if($p->dead) $this->player[$p->getName()] = ["Tag" => new FroatingText($this, $p), "Time" => time(true) + $this->rt["Time"], "Task" => $this->getServer()->getScheduler()->scheduleDelayedTask(new Task($this, [$this, "runRespawn"], [$p]), $this->rt["Time"] * 20)->getTaskId()];
	}

	public function onPlayerQuit(PlayerQuitEvent $event){
		$p = $event->getPlayer();
		if(isset($this->player[$n = $p->getName()])){
			if($this->player[$n]["Tag"] instanceof FroatingText) $this->player[$n]["Tag"]->close();
			$this->getServer()->getScheduler()->cancelTask($this->player[$n]["Task"]);
			unset($this->player[$n]);
		}
	}

	public function onPlayerDeath(PlayerDeathEvent $event){
		$p = $event->getEntity();
		if(isset($this->player[$n = $p->getName()])){
			if($this->player[$n]["Tag"] instanceof FroatingText) $this->player[$n]["Tag"]->closed = true;
			$this->getServer()->getScheduler()->cancelTask($this->player[$n]["Task"]);
		}
		$this->player[$n] = ["Tag" => new FroatingText($p, $time = time(true) + $this->rt["Time"]), "Time" => $time, "Task" => $this->getServer()->getScheduler()->scheduleDelayedTask(new Task($this, [$this, "runRespawn"], [$p]), $this->rt["Time"] * 20)->getTaskId()];
	}

	/**
	 * @priority LOWEST
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event){
		if($event->isCancelled()) return;
		$pk = $event->getPacket();
		$p = $event->getPlayer();
		if(($pk->pid() == ProtocolInfo::RESPAWN_PACKET || ($pk->pid() == ProtocolInfo::PLAYER_ACTION_PACKET && $pk->action == 7)) && isset($this->player[$n = $p->getName()])){
			if($this->player[$n]["Tag"]->closed) $this->player[$n]["Tag"] = new FroatingText($this,$p, time(true) + $this->rt["Time"]);
			else $this->player[$n]["Tag"]->f = true;
			$event->setCancelled();
			$p->setHealth(0);
			$pk = new SetHealthPacket();
			$pk->health = 0;
			$p->dataPacket($pk);
		}
	}

	public function runRespawn($p){
		$p->craftingType = 0;
		$this->getServer()->getPluginManager()->callEvent($ev = new PlayerRespawnEvent($p, $p->getSpawn()));
		$pos = $ev->getRespawnPosition();
		$pk = new RespawnPacket();
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;
		$p->dataPacket($pk);
		$p->teleport($pos);
 		$p->extinguish();
		$p->setDataProperty(Player::DATA_AIR, Player::DATA_TYPE_SHORT, 300);
		$p->deadTicks = 0;
		$p->noDamageTicks = 60;
		$p->setHealth($p->getMaxHealth());
		$p->dead = false;
		$p->removeAllEffects();
		$p->sendData($p);
		$p->sendSettings();
		$p->getInventory()->sendContents($p);
		$p->getInventory()->sendArmorContents($p);
		$p->blocked = false;
		$p->spawnToAll();
		$p->scheduleUpdate();
		unset($this->player[$p->getName()]);
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->rt = (new Config($this->getDataFolder() . "RespawnTime.yml", Config::YAML, ["Time" => 30]))->getAll();
	}

	public function saveYml(){
		$rt = new Config($this->getDataFolder() . "RespawnTime.yml", Config::YAML);
		$rt->setAll($this->rt);
		$rt->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}