<?php

namespace RubyTnTTag;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\level\Position;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\utils\TextFormat as Color;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\entity\Effect;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\level\Explosion;
use pocketmine\math\Vector3;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\particle\HeartParticle;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\entity\Human;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerMoveEvent;

class RubyTnTTag extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public $hrank = [], $config, $reload, $boolJoin = false, $wait = false, $info=false, $StartSet = false, $playerCount, $restartPopup, $startPopup, $inPlayers = [], $waitPlayer, $randTagger, $tagger, $taggerSec, $fromPopup, $gameFinish, $gameRestart, $boolPlayer=false, $boolRestart=false, $boolStart=false, $boolTagger=false;
	public $startSec, $restartSec, $randomSec, $finishSec = 600;
	public $remainSec = 50, $minusSec = 0;
	public $reloadSec;

	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->config = (new Config($this->getDataFolder() . "Point.yml", Config::YAML))->getAll();
		$this->playerCount = $this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"getCount"]), 20);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"return2Map"]), 20);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDisable(){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$player->removeAllEffects();
			$player->teleport($player->getServer()->getDefaultLevel()->getSpawn());
			$player->kick("The game is over.");
		}
		$this->saveYml();
	}

	public function saveYml(){
		arsort($this->config);
		$config=new Config($this->getDataFolder()."tnttag.yml",Config::YAML);
		$config->setAll($this->config);
		$config->save();
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[1])){
			return false;
		}elseif(!($sender instanceof \pocketmine\Player)){
			$sender->sendMessage(Color::RED . "[TNTTag] " . ($this->isKorean() ? "게임내에서 실행해주세요." : "Please run this command in-game"));
			return true;
		}else{
			$ik = $this->iskorean();
			switch(strtolower($sub[0]){
				case "score":
				case "점수":
				case "전적":
				case "s":
					if(!isset($this->config[$name = strtolower($sender->getName())])){
						$r = Color::RED. "[TNTTag] " . ($ik ? "당신은 전적이 없습니다." : "You don't have score");
					}else{
						$r = Color::YELLOW . "[TNTTag] " . ($ik ? "당신의 점수 : " : "Your score: ") . $this->config[$name];
					}
				break;
				case "rank":
				case "ranking":
				case "랭크":
				case "랭킹":
				case "r":
					arsort($this->config);
					$lists = array_chunk($this->config, 5);
					$page = min(isset($sub[1]) && is_numeric($sub[1]) && isset($lists[$sub[1] - 1]) ? $sub[1] : 1, count($lists));
					$r = Color::YELLOW . "[TNTTag] " . ($ik ? "랭킹 (페이지" : "Rank (Page") . $page . "/" . count($lists) . ") (" . count($this->config) . ")";
					if(isset($lists[$page - 1])){
						$keys = array_keys($this->config);
						foreach($lists[$page - 1] as $index => $data){
							$r .= "\n" . Color::GOLD . "    [" . (($key = ($page - 1) * 5 + $index) + 1) . "] " . $keys[$key] . " => " . $data[self::PRICE] . "\$" . ($data[self::IS_TOP] ? Color::RED . "  [TOP]" : "") . (in_array($keys[$key], $this->data[self::PLAYERS][strtolower($sender->getName())]) ? Color::AQUA . "[HAVE]" : "");
						}
					}
				break;
				case "myrank":
				case "내랭크":
				case "나의랭킹":
				case "mr":
					if(!isset($this->config[$name = strtolower($sender->getName())])){
						$r = Color::RED. "[TNTTag] " . ($ik ? "당신은 전적이 없습니다." : "You don't have score");
					}else{
						arsort($this->config);
						$rank = 1;
						foreach($this->config as $key => $score){
							if($key == $name){
								$r = Color::YELLOW . "[TNTTag] " . ($ik ? "당신의 랭킹 : " : "Your rank: ") . $rank . Color::GOLD . ",  " . ($ik ? "당신의 점수 : " : "Your score: ") . $score;
								break;
							}else{
								$rank++;
							}
						}
					}
				break;
				case "seerank":
				case "랭크보기":
				case "상대랭킹":
				case "sr":
					if(!isset($sub[1]) || $sub[1] == ""){
						$r = Color::RED . "[TNTTag] Usage: /TNTTag " . ($ik ? "상대랭킹 <플레이어명>" : "SeeRank <PlayerName>");
					}elseif(!isset($this->config[$name = strtolower($sub[1])])){
						$r = Color::RED. "[TNTTag] $sub[1]" . ($ik ? "님은 전적이 없습니다." : " don't have score");
					}else{
						arsort($this->config);
						$rank = 1;
						foreach($this->config as $key => $score){
							if($key == $name){
								$r = Color::YELLOW . "[TNTTag] $sub[1]" . ($ik ? "님의 랭킹 : " : "\'s rank: ") . $rank . Color::GOLD . ",  $sub[1]" . ($ik ? "님의 점수 : " : "\'s score: ") . $score;
								break;
							}else{
								$rank++;
							}
						}
					}
				break;
			}
			if(isset($r)){
				$sender->sendMessage($r);
			}
		}
	}
	public function onPlayerMove(\pocketmine\event\player\PlayerMoveEvent $event){
		$player = $event->getPlayer();
		if(!$player->isOp() && !isset($this->inPlayers[$player->getName()]) && $player->distance($spawn = $this->getServer()->getDefaultLevel()->getSpawn()) > 100){
			$player->teleport($spawn);
		}
	}

	public function onPlayerJoin(\pocketmine\event\player\PlayerJoinEvent $event){
		$player=$event->getPlayer();
			if(!$this->boolStart && !$this->boolRestart){
				$this->inPlayers[$player->getName()] = $player;
				if($this->boolPlayer && count($this->inPlayers) >= 5 && $this->StartSet){
					$this->boolPlayer = false;
					$event->setJoinMessage(Color::GRAY . $player->getName()  Color::YELLOW . " joined the game - " . count($this->inPlayers) . "/" . $this->getServer()->getMaxPlayers());
					$player->setGamemode(2);
					$this->getServer()->broadcastMessage(Color::GRAY ."[". Color::RED ."T-TAG". Color::GRAY ."]: ". Color::YELLOW ."Game will start in 30 seconds");
					$this->startSec=30;
					$this->startPopup=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"onStartPopup"]), 20);								 
				}elseif(!$this->boolPlayer && count($this->inPlayers) < 5 && $this->StartSet){
					$this->boolPlayer=true;
					$event->setJoinMessage(Color::GRAY ."". $player->getName() ."". Color::YELLOW ." joined the game - ". count($this->inPlayers) ."/". $this->getServer()->getMaxPlayers() ."");
					$player->setGamemode(2);
					$this->startSec=30;
					$this->getServer()->broadcastMessage();
					$this->getServer()->getScheduler()->cancelTask($this->startPopup->getTaskId());
					$this->waitPlayer=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"waitPlayers"]), 10);
				}elseif(!$this->boolPlayer && count($this->inPlayers) > 0 && !$this->StartSet){
					$this->boolPlayer=true;
					$this->StartSet=true;
					$this->wait=true;
					$event->setJoinMessage(Color::GRAY ."". $player->getName() ."". Color::YELLOW ." joined the game - ". count($this->inPlayers) ."/". $this->getServer()->getMaxPlayers() ."");
					$this->waitPlayer=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"waitPlayers"]), 10);
				}
				elseif(!$this->boolStart&&count($this->inPlayers) == 20){
					$this->boolPlayer=false;
					$this->getServer()->broadcastMessage(Color::GRAY ."". $player->getName() ."". Color::YELLOW ." joined the game - ". count($this->inPlayers) ."/". $this->getServer()->getMaxPlayers() ."");
					$this->getServer()->broadcastMessage(Color::AQUA ."Teleporting...\n". Color::GRAY ."[". Color::RED ."T-TAG". Color::GRAY ."]: ". Color::YELLOW ."After a while, the ". Color::RED ."TNTman". Color::YELLOW ." is determined\n". Color::GRAY ."[". Color::RED ."T-TAG". Color::GRAY ."]: ". Color::YELLOW ."Game has been started");
			$pos=new Position($level->getSpawn()->x,$level->getSpawn()->y,$level->getSpawn()->z,$level);
			$players->teleport($pos);
					$this->getServer()->getScheduler()->cancelTask($this->startPopup->getTaskId());
					$this->getServer()->getScheduler()->cancelTask($this->waitPlayer->getTaskId());
					$this->randomSec=5;
				    $this->randTagger=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"randomTagger"]), 20);
					$player->setGamemode(2);
				}
			}elseif(!$this->boolStart && $this->boolRestart){
				$player->sendMessage(Color::GRAY ."[". Color::RED ."T-TAG". Color::GRAY ."]: ". Color::BLUE ."This game will reload in 30 seconds");
				$player->setGamemode(2);
			}elseif(count($this->inPlayers) > 20){
			$event->setJoinMessage(Color::DARK_GRAY ."". $player->getName() ." joined the game");
			$player->setGamemode(3);
			$player->addEffect(Effect::getEffectByName("INVISIBILITY")->setAmplifier(1)->setDuration(60*1200)->setVisible(false));
			$player->sendMessage(Color::DARK_GRAY ."You are now spectator!");
	}
	}
	public function onDeath(PlayerDeathEvent $event){
		$player=$event->getEntity();
		if($player instanceof Player){
			$n=$player->getName();
		     if($this->boolStart){
		     $already=false;
		     if(!$already){
					if($this->tagger->getName()==$player->getName()){
					$already=true;
							$this->getServer()->broadcastMessage(Color::GRAY ."[". Color::RED ."T-TAG". Color::GRAY ."]: $n ". Color::YELLOW ."was blown up by ". Color::RED ."". $player->getName() ."". Color::YELLOW ."and humans got a speed effect");
							 				$p->addEffect(Effect::getEffectByName("SPEED")->setAmplifier(1)->setDuration(15));
					     	$this->getServer()->broadcastMessage(Color::GRAY ."[". Color::RED ."T-TAG". Color::GRAY ."]: ". Color::YELLOW ."After a while, ". Color::RED ."TNTman". Color::YELLOW ."will be determined");
							$this->randomSec=5;
							$this->randTagger=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"randomTagger"]), 20);
							unset($this->inPlayers[$this->tagger->getName()]);
							
			        }
			        }
			        $already=true;
					foreach($this->inPlayers as $p){
						if(($this->tagger->getName()!=$p->getName()) && $n==$p->getName()){
							$this->getServer()->broadcastMessage(Color::GRAY ."[". Color::RED ."T-TAG". Color::GRAY ."]: $n ". Color::YELLOW ."was blown up by ". Color::RED ."". $this->tagger->getName() ."". Color::YELLOW ."and humans got a speed effect");
						 				$p->addEffect(Effect::getEffectByName("SPEED")->setAmplifier(1)->setDuration(15));
							unset($this->inPlayers[$n]);
					}
			}
		}
		}
		}
	public function TnTTag(EntityDamageEvent $event){
		if($ev instanceof EntityDamageByEntityEvent){
			if($this->boolStart && $this->boolTagger){
				$player=$event->getDamager();
				$victim=$event->getEntity();
				if($player instanceof Player && $victim instanceof Player){
				   		if($this->tagger->getName()==$player->getName()){
				   			if($player->getInventory()->getItemInHand()->getId()==46){
				   				$tavec=new Vector3($this->tagger->x,$this->tagger->y,$this->tagger->z);
				   				$pvec=new Vector3($victim->x,$victim->y,$victim->z);
				   				if($tavec->distance($pvec) <= 10){
				   			foreach($this->inPlayers as $p){
				   				if($victim->getName()==$p->getName()){
				   					$player->removeAllEffects();
				   					$player->addEffect(Effect::getEffectByName("SPEED")->setAmplifier(1)->setDuration(60*1200));
				   					$player->getInventory()->clearAll();
				   					$p->removeAllEffects();
				   					$this->tagger=$p;
				   					foreach($this->getServer()->getOnlinePlayers() as $players){
				   						$players->sendTip(Color::RED ."TNTman: ". Color::GRAY ."". $player->getName() ." -> ". Color::RED ."". $victim->getName() ."");
				   					}
				   					$this->taggerItem();
				   				}
				   			}
				   				}
				   			}
				   		}
				   		foreach($this->inPlayers as $p){
				   			if($player->getName()==$p->getName()){
				   				$event->setCancelled(true);
				   			}elseif($player->getName()!=$p->getName()){
				   				$event->setCancelled(true);
			            	}
				   		}
				   }
				   return;
				   $event->setCancelled(true);
				}
			}
			if($event->getCause()===EntityDamageEvent::CAUSE_FALL){
				$event->setCancelled(true);
			}elseif($event->getCause()===EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK){
				$event->setCancelled(true);
			}
		}
	public function outGame(PlayerQuitEvent $event){
		$player=$event->getPlayer();
		$n=$player->getName();
		if($this->boolStart){
		foreach($this->inPlayers as $p){
				if($player->getName()!=$p->getName()){
					$event->setQuitMessage(false);
				}
			        }
				if($this->tagger->getName()==$n){
					$this->getServer()->getScheduler()->cancelTask($this->taggerSec->getTaskId());
					$this->minusSec+=1;
					$this->remainSec=50-$this->minusSec;
					$this->boolTagger=false;
					$event->setQuitMessage(Color::GRAY ."[". Color::RED ."T-TAG". Color::GRAY ."]: ". Color::RED ."". $player->getName() ."". Color::YELLOW ."left the game");
					$this->getServer()->broadcastMessage();
					$this->getServer()->broadcastMessage(Color::GRAY ."[". Color::RED ."T-TAG". Color::GRAY ."]: ". Color::YELLOW ."After a while, ". Color::RED ."TNTman". Color::YELLOW ."will be determined");
					$this->randomSec=5;
					unset($this->inPlayers[$player->getName()]);
					$this->randTagger=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"randomTagger"]), 20);
				}
				foreach($this->inPlayers as $p){
					if(($this->tagger->getName()!=$p->getName())&&$n==$p->getName()){
						$event->setQuitMessage(Color::GRAY ."". $player->getName() ."". Color::YELLOW ." left the game");
						unset($this->inPlayers[$player->getName()]);
					}
				}
		}else{
			foreach($this->inPlayers as $p){
						$event->setQuitMessage(false);
						unset($this->inPlayers[$player->getName()]);
				}
		}
		$player->removeAllEffects();
		$level=$player->getServer()->getDefaultLevel()->getSpawn();
		$player->teleport($level);
	}
	public function onRespawn(PlayerRespawnEvent $event){
		$player=$event->getPlayer();
		if($this->boolStart){
		$player->sendMessage(Color::DARK_GRAY."You are now spectator!");
		$player->setGamemode(3);
		$player->addEffect(Effect::getEffectByName("INVISIBILITY")->setAmplifier(1)->setDuration(60*1200));
		return;
	}
	$player->setGamemode(2);
	}
	public function waitPlayers(){
		if($this->wait){
		foreach($this->inPlayers as $p){
			$p->sendPopup(Color::AQUA."Preloading chunks...");
		}
		}
	}
	public function randomTagger(){
		if($this->randomSec==0){
		 $this->getServer()->getScheduler()->cancelTask($this->randTagger->getTaskId());
		if(!$this->boolStart && !$this->boolRestart){
			foreach($this->inPlayers as $p){
				$p->getinventory()->clearAll();
			}
			$this->tagger=$this->inPlayers[array_rand($this->inPlayers)];
			$this->boolStart=true;
			$taggerName=$this->tagger->getName();
			$this->getServer()->broadcastMessage(Color::GRAY ."[". Color::RED ."T-TAG". Color::GRAY ."]: ". Color::GOLD ."TNTman has been determined");
			$this->taggerItem();
			$this->gameFinish=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"onFinish"]), 20);
		}elseif($this->boolStart && !$this->boolRestart && count($this->inPlayers) > 0){
		$this->tagger=$this->inPlayers[array_rand($this->inPlayers)];
		$taggerName=$this->tagger->getName();
		$this->getServer()->broadcastMessage(Color::GRAY ."[". Color::RED ."T-TAG". Color::GRAY ."]: ". Color::GOLD ."TNTman has been determined");
		$this->taggerItem();
	    }
		}
	$this->randomSec--;
	}
	public function onItemCom(PlayerDropItemEvent $event){
		$event->setCancelled(true);
	}
	public function taggerItem(){
		foreach($this->inPlayers as $p){
				$p->removeAllEffects();
				$p->addEffect(Effect::getEffectByName("SPEED")->setAmplifier(1)->setDuration(15));
		}
		if(!$this->boolTagger){
			$this->boolTagger=true;
			$this->taggerSec=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"deathTagger"]), 20);
		}
		$taggerInv=$this->tagger->getInventory();
		$taggerInv->addItem(Item::get(46,0,1));
		$taggerInv->setItemInHand(Item::get(46,0,1));
		$this->tagger->sendMessage(Color::RED."You are now TNTman\n". Color::YELLOW ."Pass the TNT in ".$this->remainSec." seconds");	
		$this->tagger->addEffect(Effect::getEffectByName("INVISIBILITY")->setAmplifier(1)->setDuration(60*1200)->setVisible(false));
		$this->tagger->addEffect(Effect::getEffectByName("SPEED")->setAmplifier(2)->setDuration(60*1200)->setVisible(false));
		$this->tagger->addEffect(Effect::getEffectByName("REGENERATION" )->setAmplifier(2)->setDuration(60*1200)->setVisible(false));
		if(!$this->info){
			$this->info=true;
		   $this->fromPopup=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"inFromPopup"]), 10);
	}
	}
	public function deathTagger(){
		if($this->boolTagger){
			if($this->remainSec ==10  || $this->remainSec ==5  || $this->remainSec ==4  || $this->remainSec ==3  || $this->remainSec ==2 || $this->remainSec==1){
			$this->getServer()->broadcastMessage(Color::GRAY ."[". Color::RED ."T-TAG". Color::GRAY ."]: ". Color::YELLOW ."TNT will explode in ". $this->remainSec ." seconds");
		    }
		elseif($this->remainSec<=0){
			$this->getServer()->getScheduler()->cancelTask($this->taggerSec->getTaskId());
			$this->getServer()->broadcastMessage(Color::GRAY ."[". Color::RED ."T-TAG". Color::GRAY ."]: ". Color::RED ."". $this->tagger->getName() ."". Color::YELLOW ."was blown up by ". Color::RED ."TNT");
			$this->tagger->getInventory()->clearAll();
		    $this->boolTagger=false;
		    $this->tagger->kill();
$this->getServer()->getPluginManager()->callEvent($ev = new ExplosionPrimeEvent($this->tagger, 4));
$ex=new Explosion($this->tagger,4);
$ex->explodeB();
			$this->minusSec+=2;
		    $this->remainSec=50-$this->minusSec;
		}
		$level=$this->getServer()->getDefaultLevel();
		$vec=new Vector3($this->tagger->x,$this->tagger->y+1,$this->tagger->z,$level);
		$level->addParticle(new DustParticle($vec,255,255,255,255));
		$level->addParticle(new DustParticle($vec,255,255,255,255));
		$level->addParticle(new DustParticle($vec,255,255,255,255));
		$this->remainSec--;
	}
	}
	public function onFinish(){
		if($this->finishSec==0){
		$this->info=false;
		$this->boolStart=false;
		$this->boolRestart=true;
		$this->getServer()->getScheduler()->cancelTask($this->taggerSec->getTaskId());
		$this->getServer()->broadcastMessage(Color::GRAY ."[". Color::DARK_GREEN ."Z-SG". Color::GRAY ."]: ". Color::YELLOW ."Game is finished");
		foreach($this->getServer()->getOnlinePlayers() as $players){
			$players->removeAllEffects();
			$players->getInventory()->clearAll();
			$level=$this->getServer()->getDefaultLevel();
			$players->setHealth(20);
			$pos=new Position($level->getSpawn()->x,$level->getSpawn()->y,$level->getSpawn()->z,$level);
			$players->setGamemode(2);
			$players->teleport($pos);
		}
		if(count($this->inPlayers) > 0){
			$pk=new AddPlayerPacket();
			$level=$this->getServer()->getDefaultLevel();
		foreach($this->inPlayers as $player){
			$this->getServer()->broadcastMessage(Color::AQUA . "Winner: " . $player->getName());
			++$this->config[$player->getName()];
			$level=$this->getServer()->getDefaultLevel();
			$pk->x=$level->getSpawn()->x;
			$pk->y=$level->getSpawn()->y+2;
			$pk->z=$level->getSpawn()->z;
			$pk->clientID=$player->getClientId();
			$pk->skin=$player->getSkinData();
			$pk->slim=true;
			$pk->yaw=$player->yaw;
			$pk->pitch=$player->pitch;
			$pk->item=46;
			$pk->username="Name: ".$player->getName(). " / Score: ".$this->config[$player->getName()];
			$pk->metadata= [
				Human::DATA_FLAGS => [Human::DATA_TYPE_BYTE, false << Human::DATA_FLAG_INVISIBLE],
				Human::DATA_SHOW_NAMETAG => [Human::DATA_TYPE_BYTE, true],
				Human::DATA_NO_AI => [Human::DATA_TYPE_BYTE, true]
			];
			foreach($this->getServer()->getOnlinePlayers() as $player){
			$player->dataPacket($pk);
		}
		}
		}else{
			$this->getServer()->broadcastMessage(Color::RED."");
		}
		$this->saveYml();
		$this->inPlayers=[];
		$this->getServer()->broadcastMessage(Color::GRAY ."[". Color::DARK_GREEN ."Z-SG". Color::GRAY ."]: ". Color::DARK_RED ."All players has been dead");
		$this->restartSec=30;
		$this->restartPopup=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"onRestartPopup"]), 20);
	}
	$this->finishSec--;
	}
	public function onBlockPlace(BlockPlaceEvent $event){
		$event->setCancelled(true);
	}
	public function onRestart(){
		$this->boolRestart=false;
		$this->getServer()->broadcastMessage(Color::GRAY ."[". Color::DARK_GREEN ."Z-SG". Color::GRAY ."]: ". Color::YELLOW ."Return to the lobby...");
		$pk=new ServerTPPacket();
		$pk->address="115.68.116.209";
		$pk->port=19132;
		foreach($this->getServer()->getOnlinePlayers() as $p){
			$p->dataPacket($pk);
			}
		$this->reload=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"onRealod"]), 20);
	}
	public function onRealod(){
		$this->getServer()->getScheduler()->cancelTask($this->waitPlayer->getTaskId());
		$this->getServer()->getScheduler()->cancelTask($this->startPopup->getTaskId());
		$this->getServer()->getScheduler()->cancelTask($this->fromPopup->getTaskId());
		$this->getServer()->getScheduler()->cancelTask($this->restartPopup->getTaskId());
		$this->getServer()->getScheduler()->cancelTask($this->reload->getTaskId());
		 $this->getServer()->getScheduler()->cancelTask($this->gameFinish->getTaskId());
		$this->StartSet=false;
		$this->boolJoin=false;
		$this->boolPlayer=false;
		$this->boolTagger=false;
		$this->remainSec=50;
		$this->minusSec=0;
		$this->finishSec=600;
		$this->wait=false;
	}
	public function inFromPopup(){
		if($this->info){
		foreach($this->getServer()->getOnlinePlayers() as $p){
			if($this->tagger->getName()!=null){
				$p->sendPopup(Color::RED."TNTman: ".$this->tagger->getName()."".Color::WHITE ." / ". Color::BLUE."Remaining players: ".count($this->inPlayers).Color::RED."\nRemaining Time: ".$this->remainSec."".Color::WHITE ." / ". Color::YELLOW."Score: ".$this->config[$p->getName()]);
		}
		}
	}
	}

	public function onStartPopup(){
		if($this->startSec == 0){
			$this->getServer()->broadcastMessage(Color::AQUA . "Teleporting...\n" . Color::GRAY . "[" . Color::RED . "T-TAG" . Color::GRAY . "]: " . Color::YELLOW . "After a while, the " . Color::RED . "TNTman" . Color::YELLOW . " is determined\n" . Color::GRAY . "[" . Color::RED . "T-TAG" . Color::GRAY . "]: " . Color::YELLOW . "Game has been started");
			$this->randomSec = 1;
			$this->randTagger = $this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"randomTagger"]), 100);
		}else{
			$this->getServer()->broadcastMessage(Color::GRAY . "[" . Color::RED . "T-TAG" . Color::GRAY . "]: " . Color::YELLOW . "Starting in " . $this->startSec . " seconds");
			if($this->startSec == 1){
				$this->wait=false;
			}
		}
		$this->startSec--;
	}

	public function onRestartPopup(){
		if($this->boolRestart){
			if($this->restartSec == 0){
				$this->onRestart();
 			}else{
				$this->getServer()->broadcastMessage(Color::GRAY . "[" . Color::RED . "T-TAG" . Color::GRAY . "]: " . Color::AQUA . "Reloading in " . $this->restartSec . " seconds");
			}
		$this->restartSec--;
		}
	}

	public function getCount(){
		if($this->boolStart && count($this->inPlayers) < 2){
			$this->finishSec = 0;
			$this->onFinish();
		}
	}


	public function onPlayerChat(PlayerChatEvent $event){
 		if($this->boolStart && !$this->boolRestart){
		  if($this->tagger === ($player = $event->getPlayer())){
				$event->setFormat(Color::RED . "<[TNT맨] {%0}>: " . Color::WHITE . "{%0}");
			}elseif(isset($this->inPlayers[$player->getName()])){
				$event->setFormat(Color::GOLD . "<[생존자] {%0}>: " . Color::WHITE . "{%0}");
			}else{
				$event->setFormat(Color::DARK_GRAY . "<[관전자] {%0}>: {%1}");
			}
		}
	}

	public function onPlayerPreLogin(PlayerPreLoginEvent $event){
		if($this->boolJoin){
			$event->setKickMessage("...");
		}
	}
}