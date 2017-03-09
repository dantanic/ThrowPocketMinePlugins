<?php

namespace MineBlock\MaxHealth;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class MaxHealth extends PluginBase implements Listener{
	public function onEnable(){
		$this->player = [];
		$this->tick = 0;
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this,"onTick"]), 20);
	}
	
	public function onDisable(){
		$this->getServer()->getScheduler()->cancelTasks($this);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$rm = "Usage: /MaxHealth ";
		$mm = "[MaxHealth] ";
		$ik = $this->isKorean();
		$mh = $this->mh;
		switch(strtolower($sub[0])){
			case "set":
			case "s":
			case "설정":
				if(!isset($sub[2])){
					$r = $rm . ($ik ? "설정 <플레이어명> <체력>" : "Set(S) <PlayerName> <Health>");
				}else{
					if(!$player = $this->getServer()->getPlayer($sub[1])){
						$r = $mm . $sub[1] . ($ik ? " 는 잘못된 플레이어명입니다." : "is invalid player");
					}elseif(!is_numeric($sub[2]) || $sub[2] < 1){
						$r = $mm . $sub[2] . ($ik ? "는 잘못된 숫자입니다." : " is invalid number");
					}else{
						$sub[2] = floor($sub[2]);
						$mh["Set"][strtolower($n = $player->getName())] = $sub[2];
						$r = $mm . ($ik ? "$n 님의 최대체력을 $sub[2]로 설정했습니다." : "Set $n\'s Max health to $sub[2]");
					}
				}
			break;
			case "default":
			case "d":
			case "all":
			case "a":
			case "기본":
			case "전체":
				if(!isset($sub[1])){
					$r = $rm . ($ik ? "기본 <체력>" : "Default(D) <Health>");
				}elseif(!is_numeric($sub[1]) || $sub[1] < 1){
					$r = $mm . $sub[1] . ($ik ? "는 잘못된 숫자입니다." : " is invalid number");
				}else{
					$sub[2] = floor($sub[1]);
					$mh["Default"] = $sub[1];
					$r = $mm . ($ik ? "기본 최대체력을 $sub[1]로 설정했습니다." : "Set Default Max health to $sub[1]");
				}
			break;
			default:
				return false;
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if($this->mh !== $mh){
			$this->mh = $mh;
			$this->saveYml();
		}
		return true;
	}

	public function onDataPacketReceive(DataPacketReceiveEvent $event){
		if($event->isCancelled()) return;
		$pk = $event->getPacket();
		$p = $event->getPlayer();
		if($pk->pid() == ProtocolInfo::RESPAWN_PACKET && $p->spawned && $p->dead){ //리스폰 패킷잡아서 체력 정싱적으로 설정후 리스폰, (PMMP에서는 최대체력과 상관없이 무조건 20으로 설정)
			$p->craftingType = 0;
			$this->getServer()->getPluginManager()->callEvent($ev = new PlayerRespawnEvent($p, $p->getSpawn()));
			$p->teleport($ev->getRespawnPosition());
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
			$event->setCancelled();
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onPlayerPreLogin(PlayerPreLoginEvent $event){
		if($event->isCancelled()) return;
		$p = $event->getPlayer();
		$p->setMaxHealth(isset($this->mh["Set"][$n = strtolower($p->getName())]) ? $this->mh["Set"][$n] : $this->mh["Default"]);
		$p->setHealth(isset($this->mh["Player"][$n]) ? $this->mh["Player"][$n] : $p->getMaxHealth());
	}

	public function onTick(){
		$this->tick++;
		foreach($this->getServer()->getOnlinePlayers() as $p){
			if($p->dead) continue;
			$p->setMaxHealth(isset($this->mh["Set"][$n = strtolower($p->getName())]) ? $this->mh["Set"][$n] : $this->mh["Default"]);
			$p->setHealth($p->getHealth());
			$this->mh["Player"][$n] = $p->getHealth();
			if(!isset($this->player[$n]) || $this->player[$n]->closed == true){
				$this->player[$n] = new FroatingText($this, $p);
			}
			$this->player[$n]->setName("§c§lHealth: §4" . $p->getHealth() . "§r§c/§l§4" . $p->getMaxHealth() . " §c§n" . (($p->getHealth() / $p->getMaxHealth()) * 100) . "§r§c%");
		}
		if($this->tick >= 60){
			$this->tick = 0;
			$this->saveYml();
		}
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->mh = (new Config($this->getDataFolder() . "MaxHealth.yml", Config::YAML, ["Default" => 20, "Set" => [], "Player" => []]))->getAll();
	}

	public function saveYml(){
		$mh = new Config($this->getDataFolder() . "MaxHealth.yml", Config::YAML);
		$mh->setAll($this->mh);
		$mh->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}