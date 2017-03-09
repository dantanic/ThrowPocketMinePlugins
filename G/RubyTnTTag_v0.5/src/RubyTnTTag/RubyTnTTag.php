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
use pocketmine\utils\TextFormat;
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
class RubyTnTTag extends PluginBase implements Listener{
	public $hrank=[],$config,$reload,$boolJoin=false,$wait=false,$info=false,$StartSet=false,$playerCount,$restartPopup,$startPopup,$inPlayers=[],$waitPlayer,$randTagger,$tagger,$taggerSec,$fromPopup,$gameFinish,$gameRestart,$boolPlayer=false,$boolRestart=false,$boolStart=false,$boolTagger=false;
	public $startSec,$restartSec,$randomSec,$finishSec=600;
	public $remainSec=50,$minusSec=0;
	public $reloadSec;
	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->config=(new Config($this->getDataFolder()."tnttag.yml",Config::YAML,[]))->getAll();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->playerCount=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"getCount"]), 20);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"outSpawn"]), 20);
	}
	public function onDisable(){
		foreach($this->getServer()->getOnlinePlayers() as $players){
			$players->removeAllEffects();
			$level=$players->getServer()->getDefaultLevel()->getSpawn();
			$players->teleport($level);
		    $players->kick("The game is over.");
		}
		$this->saveYml();
	}
	public function saveYml(){
		arsort($this->config);
		$config=new Config($this->getDataFolder()."tnttag.yml",Config::YAML);
		$config->setAll($this->config);
		$config->save();
	}
	public function onCommand(CommandSender $sender,Command $command,$label,array $args){
		if($sender instanceof Player){
		if($command->getName()=="lobby"){
			$p=$this->getServer()->getPlayer($sender->getName());
			$pk=new ServerTPPacket();
			$pk->address="115.68.116.209";
			$pk->port=19132;
			$sender->sendMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."Return to the lobby...");
			$p->dataPacket($pk);
		}elseif($command->getName()=="score"){
			$sender->sendMessage(TextFormat::YELLOW ."Your score: ".$this->config[$sender->getName()]);
		}elseif($command->getName()=="rank"){
			$config=$this->config;
			arsort($config);
		    $page=1;
			if(isset($args[0])&&is_numeric($args[0])) $page=max(floor($args[0]),1);
			$list=array_chunk($config,5,true);
			if($page>=($c=count($list)))$page=$c;
			$sender->sendMessage();
			$num=($page-1)*5;
			if($c>0){
				foreach($list[$page-1] as $k=>$v){
					$num++;
					$sender->sendMessage("$k : $v");
				}
			}
		}elseif($command->getName()=="myrank"){
			$num=1;
			$config=$this->config;
			arsort($config);
			if(!isset($config[$sender->getName()])){
				$sender->sendMessage(TextFormat::RED. "You don't have score");
				return;
			}
			foreach($config as $k=>$v){
				$num++;
				if($sender->getName()==$k){
					$sender->sendMessage(TextFormat::YELLOW. "Your rank: $num");
				}
			}
		}elseif($command->getName()=="seerank"){
			if(!isset($args[0])){
				$sender->sendMessage("Usage: /seerank <player>");
				return;
			}
			$config=$this->config;
			arsort($config);
			$num=1;
			$config=$this->config;
			arsort($config);
			if(!isset($config[$args[0]])){
				$sender->sendMessage(TextFormat::RED ."This player couldn't have socre");
				return;
			}
			foreach($config as $k=>$v){
				$num++;
				if($args[0]==$k){
					$sender->sendMessage(TextFormat::YELLOW ."$args[0] 's rank: $num");
				}
			}
		}elseif($command->getName()=="particle"){
			if(!isset($this->config[$sender->getName()])){
				$sender->sendMessage(TextFormat::RED."You don't have score");
				return;
			}
			if($this->config[$sender->getName()] < 40){
				$sender->sendMessage(TextFormat::RED."You didn't reach 40 entirely");
			}else{
				if(!isset($this->hrank[$sender->getName()])){
					$this->hrank[$sender->getName()]=true;
					$sender->sendMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."Particles turned on");
				}else{
					unset($this->hrank[$sender->getName()]);
					$sender->sendMessage(TextFormat::GRAY."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."Particles turned off");
				}
			}
		}
		}else return;
	}
	public function onMove(PlayerMoveEvent $ev){
		$player=$ev->getPlayer();
		if(isset($this->hrank[$player->getName()])){
			$vec=new Vector3($player->x,$player->y,$player->z);
			$level=$this->getServer()->getDefaultLevel();
			$level->addParticle(new HeartParticle($vec,2));
		}
	}
	public function inGame(PlayerJoinEvent $ev){
		$player=$ev->getPlayer();
		$n=$player->getName();
		if(!isset($this->config[$n])){
			$this->config[$n]=0;
			$this->saveYml();
		}
			if(!$this->boolStart && !$this->boolRestart){
				$this->inPlayers[$player->getName()]=$player;
				if($this->boolPlayer && count($this->inPlayers) >= 5 && $this->StartSet){
					$this->boolPlayer=false;
					$ev->setJoinMessage(TextFormat::GRAY ."". $player->getName() ."". TextFormat::YELLOW ." joined the game - ". count($this->inPlayers) ."/". $this->getServer()->getMaxPlayers() ."");
					$player->setGamemode(2);
					$this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."Game will start in 30 seconds");
					$this->startSec=30;
					$this->startPopup=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"onStartPopup"]), 20);								 
				}elseif(!$this->boolPlayer && count($this->inPlayers) < 5 && $this->StartSet){
					$this->boolPlayer=true;
					$ev->setJoinMessage(TextFormat::GRAY ."". $player->getName() ."". TextFormat::YELLOW ." joined the game - ". count($this->inPlayers) ."/". $this->getServer()->getMaxPlayers() ."");
					$player->setGamemode(2);
					$this->startSec=30;
					$this->getServer()->broadcastMessage();
					$this->getServer()->getScheduler()->cancelTask($this->startPopup->getTaskId());
					$this->waitPlayer=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"waitPlayers"]), 10);
				}elseif(!$this->boolPlayer && count($this->inPlayers) > 0 && !$this->StartSet){
					$this->boolPlayer=true;
					$this->StartSet=true;
					$this->wait=true;
					$ev->setJoinMessage(TextFormat::GRAY ."". $player->getName() ."". TextFormat::YELLOW ." joined the game - ". count($this->inPlayers) ."/". $this->getServer()->getMaxPlayers() ."");
					$this->waitPlayer=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"waitPlayers"]), 10);
				}
				elseif(!$this->boolStart&&count($this->inPlayers) == 20){
					$this->boolPlayer=false;
					$this->getServer()->broadcastMessage(TextFormat::GRAY ."". $player->getName() ."". TextFormat::YELLOW ." joined the game - ". count($this->inPlayers) ."/". $this->getServer()->getMaxPlayers() ."");
					$this->getServer()->broadcastMessage(TextFormat::AQUA ."Teleporting...\n". TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."After a while, the ". TextFormat::RED ."TNTman". TextFormat::YELLOW ." is determined\n". TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."Game has been started");
			$pos=new Position($level->getSpawn()->x,$level->getSpawn()->y,$level->getSpawn()->z,$level);
			$players->teleport($pos);
					$this->getServer()->getScheduler()->cancelTask($this->startPopup->getTaskId());
					$this->getServer()->getScheduler()->cancelTask($this->waitPlayer->getTaskId());
					$this->randomSec=5;
				    $this->randTagger=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"randomTagger"]), 20);
					$player->setGamemode(2);
				}
			}elseif(!$this->boolStart && $this->boolRestart){
				$player->sendMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::BLUE ."This game will reload in 30 seconds");
				$player->setGamemode(2);
			}elseif(count($this->inPlayers) > 20){
			$event->setJoinMessage(TextFormat::DARK_GRAY ."". $player->getName() ." joined the game");
			$player->setGamemode(3);
			$player->addEffect(Effect::getEffectByName("INVISIBILITY")->setAmplifier(1)->setDuration(60*1200)->setVisible(false));
			$player->sendMessage(TextFormat::DARK_GRAY ."You are now spectator!");
	}
	}
	public function onDeath(PlayerDeathEvent $ev){
		$player=$ev->getEntity();
		if($player instanceof Player){
			$n=$player->getName();
		     if($this->boolStart){
		     $already=false;
		     if(!$already){
					if($this->tagger->getName()==$player->getName()){
					$already=true;
							$this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: $n ". TextFormat::YELLOW ."was blown up by ". TextFormat::RED ."". $player->getName() ."". TextFormat::YELLOW ."and humans got a speed effect");
							 				$p->addEffect(Effect::getEffectByName("SPEED")->setAmplifier(1)->setDuration(15));
					     	$this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."After a while, ". TextFormat::RED ."TNTman". TextFormat::YELLOW ."will be determined");
							$this->randomSec=5;
							$this->randTagger=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"randomTagger"]), 20);
							unset($this->inPlayers[$this->tagger->getName()]);
							
			        }
			        }
			        $already=true;
					foreach($this->inPlayers as $p){
						if(($this->tagger->getName()!=$p->getName()) && $n==$p->getName()){
							$this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: $n ". TextFormat::YELLOW ."was blown up by ". TextFormat::RED ."". $this->tagger->getName() ."". TextFormat::YELLOW ."and humans got a speed effect");
						 				$p->addEffect(Effect::getEffectByName("SPEED")->setAmplifier(1)->setDuration(15));
							unset($this->inPlayers[$n]);
					}
			}
		}
		}
		}
	public function TnTTag(EntityDamageEvent $ev){
		if($ev instanceof EntityDamageByEntityEvent){
			if($this->boolStart && $this->boolTagger){
				$player=$ev->getDamager();
				$victim=$ev->getEntity();
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
				   						$players->sendTip(TextFormat::RED ."TNTman: ". TextFormat::GRAY ."". $player->getName() ." -> ". TextFormat::RED ."". $victim->getName() ."");
				   					}
				   					$this->taggerItem();
				   				}
				   			}
				   				}
				   			}
				   		}
				   		foreach($this->inPlayers as $p){
				   			if($player->getName()==$p->getName()){
				   				$ev->setCancelled(true);
				   			}elseif($player->getName()!=$p->getName()){
				   				$ev->setCancelled(true);
			            	}
				   		}
				   }
				   return;
				   $ev->setCancelled(true);
				}
			}
			if($ev->getCause()===EntityDamageEvent::CAUSE_FALL){
				$ev->setCancelled(true);
			}elseif($ev->getCause()===EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK){
				$ev->setCancelled(true);
			}
		}
	public function outGame(PlayerQuitEvent $ev){
		$player=$ev->getPlayer();
		$n=$player->getName();
		if($this->boolStart){
		foreach($this->inPlayers as $p){
				if($player->getName()!=$p->getName()){
					$ev->setQuitMessage(false);
				}
			        }
				if($this->tagger->getName()==$n){
					$this->getServer()->getScheduler()->cancelTask($this->taggerSec->getTaskId());
					$this->minusSec+=1;
					$this->remainSec=50-$this->minusSec;
					$this->boolTagger=false;
					$ev->setQuitMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::RED ."". $player->getName() ."". TextFormat::YELLOW ."left the game");
					$this->getServer()->broadcastMessage();
					$this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."After a while, ". TextFormat::RED ."TNTman". TextFormat::YELLOW ."will be determined");
					$this->randomSec=5;
					unset($this->inPlayers[$player->getName()]);
					$this->randTagger=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"randomTagger"]), 20);
				}
				foreach($this->inPlayers as $p){
					if(($this->tagger->getName()!=$p->getName())&&$n==$p->getName()){
						$ev->setQuitMessage(TextFormat::GRAY ."". $player->getName() ."". TextFormat::YELLOW ." left the game");
						unset($this->inPlayers[$player->getName()]);
					}
				}
		}else{
			foreach($this->inPlayers as $p){
						$ev->setQuitMessage(false);
						unset($this->inPlayers[$player->getName()]);
				}
		}
		$player->removeAllEffects();
		$level=$player->getServer()->getDefaultLevel()->getSpawn();
		$player->teleport($level);
	}
	public function onRespawn(PlayerRespawnEvent $ev){
		$player=$ev->getPlayer();
		if($this->boolStart){
		$player->sendMessage(TextFormat::DARK_GRAY."You are now spectator!");
		$player->setGamemode(3);
		$player->addEffect(Effect::getEffectByName("INVISIBILITY")->setAmplifier(1)->setDuration(60*1200));
		return;
	}
	$player->setGamemode(2);
	}
	public function waitPlayers(){
		if($this->wait){
		foreach($this->inPlayers as $p){
			$p->sendPopup(TextFormat::AQUA."Preloading chunks...");
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
			$this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::GOLD ."TNTman has been determined");
			$this->taggerItem();
			$this->gameFinish=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"onFinish"]), 20);
		}elseif($this->boolStart && !$this->boolRestart && count($this->inPlayers) > 0){
		$this->tagger=$this->inPlayers[array_rand($this->inPlayers)];
		$taggerName=$this->tagger->getName();
		$this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::GOLD ."TNTman has been determined");
		$this->taggerItem();
	    }
		}
	$this->randomSec--;
	}
	public function onItemCom(PlayerDropItemEvent $ev){
		$ev->setCancelled(true);
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
		$this->tagger->sendMessage(TextFormat::RED."You are now TNTman\n". TextFormat::YELLOW ."Pass the TNT in ".$this->remainSec." seconds");	
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
			$this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."TNT will explode in ". $this->remainSec ." seconds");
		    }
		elseif($this->remainSec<=0){
			$this->getServer()->getScheduler()->cancelTask($this->taggerSec->getTaskId());
			$this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::RED ."". $this->tagger->getName() ."". TextFormat::YELLOW ."was blown up by ". TextFormat::RED ."TNT");
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
		$this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::DARK_GREEN ."Z-SG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."Game is finished");
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
			$this->getServer()->broadcastMessage(TextFormat::AQUA ."Winner: ". $player->getName());
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
			$pk->metadata= [ Human::DATA_FLAGS => [ Human::DATA_TYPE_BYTE,0 << Human::DATA_FLAG_INVISIBLE ],Human::DATA_SHOW_NAMETAG => [ Human::DATA_TYPE_BYTE,1 ],Human::DATA_NO_AI => [Human::DATA_TYPE_BYTE,1 ]];
			foreach($this->getServer()->getOnlinePlayers() as $p){
			$p->dataPacket($pk);
		}
		}
		}else{
			$this->getServer()->broadcastMessage(TextFormat::RED."");
		}
		$this->saveYml();
		$this->inPlayers=[];
		$this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::DARK_GREEN ."Z-SG". TextFormat::GRAY ."]: ". TextFormat::DARK_RED ."All players has been dead");
		$this->restartSec=30;
		$this->restartPopup=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"onRestartPopup"]), 20);
	}
	$this->finishSec--;
	}
	public function onBlockPlace(BlockPlaceEvent $ev){
		$ev->setCancelled(true);
	}
	public function onRestart(){
		$this->boolRestart=false;
		$this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::DARK_GREEN ."Z-SG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."Return to the lobby...");
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
				$p->sendPopup(TextFormat::RED."TNTman: ".$this->tagger->getName()."".TextFormat::WHITE ." / ". TextFormat::BLUE."Remaining players: ".count($this->inPlayers).TextFormat::RED."\nRemaining Time: ".$this->remainSec."".TextFormat::WHITE ." / ". TextFormat::YELLOW."Score: ".$this->config[$p->getName()]);
		}
		}
	}
	}
	public function onStartPopup(){
		if($this->startSec==5) $this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."Starting in 5 seconds");
		if($this->startSec==4) $this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."Starting in 4 seconds");
		if($this->startSec==3) $this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."Starting in 3 seconds");
		if($this->startSec==2) $this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."Starting in 2 seconds");
		if($this->startSec==1) {
		$this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."Starting in 1 second");
		$this->wait=false;
		}
		if($this->startSec==0){ $this->getServer()->broadcastMessage(TextFormat::AQUA ."Teleporting...\n". TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."After a while, the ". TextFormat::RED ."TNTman". TextFormat::YELLOW ." is determined\n". TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::YELLOW ."Game has been started");
		$this->randomSec=1;
		$this->randTagger=$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this,[$this,"randomTagger"]), 100);
		}
		$this->startSec--;
	}
	public function onRestartPopup(){
		if($this->boolRestart){
		if($this->restartSec==5) $this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::AQUA ."Reloading in 5 seconds");
		if($this->restartSec==4) $this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::AQUA ."Reloading in 4 seconds");
		if($this->restartSec==3) $this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::AQUA ."Reloading in 3 seconds");
		if($this->restartSec==2) $this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::AQUA ."Reloading in 2 seconds");
		if($this->restartSec==1) $this->getServer()->broadcastMessage(TextFormat::GRAY ."[". TextFormat::RED ."T-TAG". TextFormat::GRAY ."]: ". TextFormat::AQUA ."Reloading in 1 second");
		if($this->restartSec==0) {
			$this->onRestart();
	}
	$this->restartSec--;
	}
	}
	public function getCount(){
		if($this->boolStart){
		if(count($this->inPlayers) <= 1){
			$this->finishSec=0;
			$this->onFinish();
		}
		}
	}
	public function outSpawn(){
		foreach($this->getServer()->getOnlinePlayers() as $p){
			if(!isset($this->inPlayers[$p->getName()])){
				$sp=$this->getServer()->getDefaultLevel()->getSpawn();
				$pos=new Vector3($p->x,$p->y,$p->z);
				if($pos->distance($sp) > 100){
					$p->teleport($sp);
				}
			}
		}
	}
	public function onChat(PlayerChatEvent $ev){
		if($this->boolStart && !$this->boolRestart){
		  if($this->tagger->getName()!=$ev->getPlayer()->getName()){
			foreach($this->inPlayers as $p){
				if($ev->getPlayer()->getName()==$p->getName()){
					$ev->setCancelled(true);
					$this->getServer()->broadcastMessage(TextFormat::GOLD."<[Human] ".$ev->getPlayer()->getName().">: ".TextFormat::WHITE.$ev->getMessage());
				}
			}
				if(!isset($this->inPlayers[$ev->getPlayer()->getName()])){
				$ev->setCancelled(true);
				$this->getServer()->broadcastMessage(TextFormat::DARK_GRAY."<[Spectator] ".$ev->getPlayer()->getName().">: ".$ev->getMessage());
			}
			
		  }else{
				$ev->setCancelled(true);
				$this->getServer()->broadcastMessage(TextFormat::RED."<[TNTman] ".$ev->getPlayer()->getName().">: ".TextFormat::WHITE.$ev->getMessage());
			}
		}
	}
	public function onPre(PlayerPreLoginEvent $ev){
		if($this->boolJoin){
			$ev->setKickMessage("...");
		}
	}
}