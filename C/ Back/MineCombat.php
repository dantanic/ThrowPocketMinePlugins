<?php

/*
__PocketMine Plugin__
name=MineCombat
version=1.0
author=onebone
class=MineCombat
apiversion=12
*/

class MineCombat implements Plugin{
	private $api, $kill, $death, $hit, $recentDeath, $isKilled;
	static $team, $foghorn;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api= $api;
	}

	public function init(){
		self::$team["Blue"] = array();
		self::$team["Red"] = array();
		self::$foghorn = array();
		$this->kill = array();
		$this->death = array();
		$this->hit = array();
		$this->recentDeath = array();
		$this->api->console->register("rank", "<Blue ed>", array($this, "commandHandler"));
		$this->api->ban->cmdWhitelist("rank");
		
		$this->api->addHandler("player.spawn", array($this, "handler"));
		$this->api->addHandler("player.join", array($this, "handler"));
		$this->api->addHandler("player.quit", array($this, "handler"));
		$this->api->addHandler("player.death", array($this, "handler"));
		$this->api->addHandler("entity.health.change", array($this, "handler"));
		$this->api->addHandler("player.interact", array($this, "handler"));
		$this->api->addHandler("player.respawn", array($this, "handler"));
		$this->api->addHandler("player.action", array($this, "handler"));
		$this->api->addHandler("player.gun.hit", array($this, "handler"));
		$this->api->addHandler("player.pickup", array($this, "handler"));
		//$this->api->addHandler("item.drop", array($this, "handler"));
	}
	
	public function __destruct(){}
	
	public function commandHandler($cmd, $param, $issuer, $alias = ""){
		switch($cmd){
			case "rank":
			$sub = strtolower($param[0]);
			switch($sub){
				case "red":
				arsort($this->kill);
				$a = 1;
				$output .= FORMAT_BLUE."-------- Red TEAM RANK ----------\n".FORMAT_RESET;
				foreach($this->kill as $key => $value){
					if(isset(self::$team["Red"][$key])){
						$output .= "##$a : $key {$value}kills / ".$this->death[$key]."\n";
						$a++;
					}
				}
				break;
				case "blue":
				arsort($this->kill);
				$a = 1;
				$output .= FORMAT_RED."-------- Blue TEAM RANK ----------\n".FORMAT_RESET;
				foreach($this->kill as $key => $value){
					if(isset(self::$team["Blue"][$key])){
						$output .= "##$a : $key {$value}kill / ".$this->death[$key]."death\n";
						$a++;
					}
				}
				break;
			}
			break;
		}
		return $output;
	}
	
	public function handler($data, $event){
		$output = "[마인컴뱃] ";
		switch($event){
			case "player.join":
			$this->gun[$data->username] = new Gun($data->username);
			$Blue = count(self::$team["Blue"]);
			$team = "";
			$Red = count(self::$team["Red"]);
			if($Blue > $Red){
				self::$team["Red"][$data->username] = isset($this->data[$data->username]) ? $this->data[$data->username] : 500;
				$team = "Red";
			}elseif($Blue < $Red){
				self::$team["Blue"][$data->username] = isset($this->data[$data->username]) ? $this->data[$data->username] : 500;
				$team = "Blue";
			}else{
				$random = rand(0, 1);
				if($random == 0){
					self::$team["Blue"][$data->username] = isset($this->data[$data->username]) ? $this->data[$data->username] : 500;
					$team = "Blue";
				}else{
					self::$team["Red"][$data->username] = isset($this->data[$data->username]) ? $this->data[$data->username] : 500;
					$team = "Red";
				}
			}
			$this->death[$data->username] = 0;
			$this->kill[$data->username] = 0;
			break;
			case "player.spawn":
			if($data->hasItem(264, 0) == false){
				$data->addItem(264,0,1);
			}
			/*if($data->hasItem(267, 0) == false){
				$data->addItem(267, 0, 1);
			}*/
			self::$foghorn[$data->username] = true;
			$this->api->schedule(60, array($this, "delete"), $data->username);
			$team = isset(self::$team["Blue"][$data->username]) ? FORMAT_RED."Blue".FORMAT_RESET : FORMAT_BLUE."Red".FORMAT_RESET;
			$this->api->chat->broadcast($output.$data->username." 님이 {$team}팀으로 입장하였습니다");
			$this->isKilled[$data->username] = false;
			break;
			case "player.quit":
			$this->data[$data->username] = isset(self::$team["Blue"][$data->username]) ? self::$team["Blue"][$data->username] : self::$team["Red"][$data->username];
			$team = isset(self::$team["Blue"][$data->username]) ? FORMAT_RED."Blue".FORMAT_RESET : FORMAT_RED."Red".FORMAT_RESET;
			if(isset(self::$team["Blue"][$data->username])){
				unset(self::$team["Blue"][$data->username]);
			}else{
				unset(self::$team["Red"][$data->username]);
			}
			$this->gun[$data->username] = null;
			$this->kill[$data->username] = null;
			$this->death[$data->username] = null;
			unset($this->gun[$data->username]);
			unset($this->kill[$data->username]);
			unset($this->death[$data->username]);
			if($this->api->ban->isBanned($data->username) == false and $this->api->ban->isIPBanned($data->ip) == false){
				$this->api->chat->broadcast($output."{$team}팀의 ".$data->username."님이 게임에서 나갔습니다.");
			}
			break;
			case "player.death":
			$data["cause"] = $this->api->entity->get($data["cause"]);
			if($data["cause"] instanceof Entity and $data["cause"]->player instanceof Player){
				$this->kill[$data["cause"]->player->username]++;
				$data["cause"]->player->sendChat("[100xp] 킬");
				if($data["cause"]->getHealth() <= 4){
					$data["cause"]->player->sendChat("[25xp] 위기일발"); // Close call
				}
				if(isset($this->hit[$data["player"]->username])){
					if($this->hit[$data["player"]->username] != $data["player"]->username){
						$data["cause"]->player->sendChat("[25xp] 동료의 보호"); // Protect colleague
					}
				}
				if(isset($this->recentDeath[$data["cause"]->player->username]) and $this->recentDeath[$data["cause"]->player->username] == $data["player"]->username){
					unset($this->recentDeath[$data["cause"]->player->username]);
					$data["cause"]->player->sendChat("[50xp] 보복");
				}
				$this->recentDeath[$data["player"]->username] = $data["cause"]->player->username;
				$this->api->chat->broadcast("[마인컴뱃] ".$this->getTeam($data["cause"]->player->username)." ".$data["cause"]->player->username." - GUN -> ".$this->getTeam($data["player"]->username)." ".$data["player"]->username);
				$this->isKilled[$data["player"]->username] = true;
			}
			$this->death[$data["player"]->username]++;
			break;
			case "entity.health.change":
			$e = $this->api->entity->get($data["cause"]);
			if($e instanceof Entity and $e->player instanceof Player){
				if(isset($this->heal[$data["entity"]->player->username])){
					$this->cancel[$data["entity"]->player->username] = true;
					unset($this->heal[$data["entity"]->player->username]);
				}else{
					$this->api->schedule(200, array($this, "heal"), $data["entity"]->player->username);
					$this->heal[$data["entity"]->player->username] = true;
				}
				if($this->isEnemy($e->player->username, $data["entity"]->player->username)){
					$this->hit[$e->player->username] = $data["entity"]->player->username;
					$this->api->schedule(80, array($this, "cancelHits"), $e->player->username);
				}
			}
			break;
			case "player.interact":
			$target = $data["targetentity"];
			$entity = $data["entity"];
			if($target->player instanceof Player and $entity->player instanceof Player){
				if(isset(self::$foghorn[$target->player->username])){
					return false;
				}
				if((isset(self::$team["Blue"][$target->player->username]) and isset(self::$team["Blue"][$entity->player->username])) or (isset(self::$team["Red"][$target->player->username]) and isset(self::$team["Red"][$entity->player->username]))){
					$entity->player->sendChat($output."그는 당신의 팀입니다!");
					return false;
				}
			}
			break;
			case "player.respawn":
			if($data->hasItem(264, 0) === false){
				$data->addItem(264,0,1);
			}
			/*if($data->hasItem(267, 0) === false){
				$data->addItem(267, 0, 1);
			}*/
			foreach($this->api->player->getAll() as $p){
			//	$p = $this->api->player->get($o, false);
				if(!$this->isEnemy($p->username, $data->username)){
					$origin = $data->username;
					$target = $p->username;
					$this->api->player->teleport($origin, $target);
					break;
				}
			}
			self::$foghorn[$data->username] = true;
			$data->sendChat("[마인컴뱃] 리스폰 후 5초간 무적입니다");
			$this->api->schedule(100, array($this, "delete"), $data->username);
			$this->isKilled[$data->username] = false;
			$this->gun[$data->username]->setAmmoCount(200);
			break;
			case "player.action":
				$item =$data["item"];
				if($item == 264){
				$success = $this->gun[$data["player"]->username]->fire();
				if(!$success){
					$data["player"]->sendChat("[마인컴뱃] 총알이 없습니다.");
				}
			}
			break;
			case "player.gun.hit":
			if($data["player"]->entity->getHealth() <= 0){
				if(!$this->isKilled[$data["player"]->username]){
				$this->kill[$data["cause"]->username]++;
				$data["cause"]->sendChat("[100xp] 킬");
				if($data["cause"]->entity->getHealth() <= 4){
					$data["cause"]->sendChat("[25xp] 위기일발"); // Close call
				}
				if(isset($this->hit[$data["player"]->username])){
					$data["cause"]->sendChat("[25xp] 동료의 보호"); // Protect colleague
				}
				if(isset($this->recentDeath[$data["cause"]->username]) and $this->recentDeath[$data["cause"]->username] == $data["player"]->username){
					unset($this->recentDeath[$data["cause"]->username]);
					$data["cause"]->sendChat("[50xp] 보복");
				}
				$this->recentDeath[$data["player"]->username] = $data["cause"]->username;
				$this->isKilled[$data["player"]->username] = true;
				$team = $this->getTeam($data["cause"]->username) == "Blue" ? FORMAT_RED."Blue".FORMAT_RESET : FORMAT_BLUE."Red".FORMAT_RESET;
				$team2 = $this->getTeam($data["cause"]->username) == "Red" ? FORMAT_RED."Blue".FORMAT_RESET : FORMAT_BLUE."Red".FORMAT_RESET;
				$this->api->chat->broadcast("[마인컴뱃] ".$this->getTeam($data["cause"]->username)." ".$data["cause"]->username." - GUN -> ".$this->getTeam($data["player"]->username)." ".$data["player"]->username);
				}
			}
			break;
			case "player.pickup":
			if($data["block"] == 264){
				$this->gun[$data["player"]->username]->plusAmmoCount(100);
				$data["entity"]->close();
				return false;
			}
			break;
		}
	}
	
	public function heal($username){
		if(isset($this->cancel[$username])){
			unset($this->cancel[$username]);
			return false;
		}
		$player = $this->api->player->get($username, false);
	//	$player->entity->setHealth(20);
	}
	
	public static function isFoghorn($username){
		return isset(self::$foghorn[$username]) ? true : false;
	}
	
	public function delete($username){
		unset(self::$foghorn[$username]);
	}	
	
	public function cancelHits($username){
		unset($this->hit[$username]);
	}
	
	public static function isEnemy($player1, $player2){
		if((isset(self::$team["Blue"][$player1]) and isset(self::$team["Red"][$player2])) or (isset(self::$team["Red"][$player1]) and isset(self::$team["Blue"][$player2]))){
			return true;
		}else{
			return false;
		}
	}
	
	public function getTeam($player){
		return isset(self::$team["Blue"][$player]) ? "Blue" : "Red";
	}
}

class Gun{
	private $ammo, $player, $fire_speed;
	
	public function __construct($player, $ammo = 200, $fire_speed = 10){
		$this->player = $player;
		$this->api = ServerAPI::request()->api;
		$this->server = ServerAPI::request();
		$this->fire_speed = $fire_speed;
		$this->ammo = $ammo;
	}
	
	public function fire(){
		if($this->ammo <= 0){
			return false;
		}
		$player = $this->api->player->get($this->player, false);
		if($player == false){
			return false;
		}
		$yaw = $player->entity->yaw;
		$pitch = $player->entity->pitch;
		$sin = -sin($yaw/180 * M_PI);
		$cos = cos($yaw/180*M_PI);
		$tan = -sin($pitch/180*M_PI);
		$pcos = cos($pitch/180*M_PI);
		$cnt = 0;
		$online = $player->level->players;
		$loc = array();
		foreach($online as $p){
			$loc[$p->username] = array(
				$p->entity->x,
				$p->entity->y + 1,
				$p->entity->z
			);
		}
		unset($loc[$player->username]);
		$x = round($player->entity->x);
		$y = round($player->entity->y);
		$z = round($player->entity->z);
		foreach($loc as $key => $value){ // Removing colleague
			if(MineCombat::isEnemy($key, $player->username) == false){
				unset($loc[$key]);
			}
		}
			$pk = new ExplodePacket;
			$pk->x = $x;
			$pk->y = $y;
			$pk->z = $z;
			$pk->radius = 1;
			$pk->records = array();
			$this->api->player->broadcastPacket($player->entity->level->players,$pk);
		do{
			$xx = $x + round((0.4 + $cnt) * $sin * $pcos);
			$yy = $y + round((0.4+ $cnt) * $tan);
			$zz = $z + round((0.4 + $cnt) * $cos * $pcos);
	 		$B = $player->level->getBlock(new Vector3($xx, $yy, $zz))->getID();
	 		console("$xx : $yy : $zz");
			if($B !== 0) break;
			foreach($loc as $key => $value){
				if($xx - 2 < $value[0] and $xx + 2 > $value[0] and $yy - 3 < $value[1] and $yy + 3 > $value[1] and $zz + 2 > $value[2] and $zz - 2 < $value[2]){
					if(MineCombat::isFoghorn($this->api->player->get($key, false)->username)){
						break 2;
					}
					$this->api->player->get($key, false)->entity->harm(10 - ceil(($cnt * 0.1)));
					$this->api->dhandle("player.gun.hit", array("cause" => $player, "player" => $this->api->player->get($key, false)));
					break 2;
				}
			}
			$cnt++;
		}while($cnt < 60); // Fire range is 60 blocks!
		$this->ammo--;
		return true;
	}
	
	public function getAmmoCount(){
		return $this->ammo;
	}
	
	public function setAmmoCount($cnt){
		$this->ammo = $cnt;
	}
	
	public function plusAmmoCount($cnt){
		$this->ammo += $cnt;
	}
}