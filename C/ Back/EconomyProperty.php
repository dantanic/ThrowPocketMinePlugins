<?php
/*
__PocketMine Plugin__
name=EconomyProperty
version=1.0.3
author=onebone
apiversion=12,13
class=EconomyProperty
*/

class EconomyProperty implements Plugin{
	private $api, $pos1, $pos2, $property, $tap;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->pos = array();
		$this->tap = array();
	}
	
	public function init(){
		if(!isset($this->api->economy) or !$this->api->economy instanceof EconomyAPI){
			console("[ERROR] Cannot find EconomyLand");
			$this->api->console->run("stop");
			return;
		}
		@mkdir(DATA_PATH."plugins/EconomyProperty");
		try{
			$this->property = new SQLite3(DATA_PATH."plugins/EconomyProperty/Properties.sqlite3");
			$this->property->exec(
				"CREATE TABLE IF NOT EXISTS Property(
					landNum INTEGER PRIMARY KEY AUTOINCREMENT,
					owner TEXT,
					price INTEGER,
					x INTEGER,
					y INTEGER,
					z INTEGER,
					level TEXT,
					startX INTEGER,
					startZ INTEGER,
					landX INTEGER,
					landZ INTEGER
				);"
			);
		}catch(Exception $e){
			console("[ERROR] Unknown error has been occurred during opening the data file. Error : ".$e);
			return;
		}
		$this->api->console->register("pr", "Fast Property", array($this, "Commander"));
		$this->api->addHandler("player.block.touch", array($this, "onTouch"));
		EconomyPropertyAPI::set($this);
	}
	
	public function __destruct(){}
	
	public function Commander($cmd, $sub, $issuer){
		if(!$issuer instanceof Player) return false;
		$r = "/» [FastProperty] ";
		$pos = $this->pos;
		$n = $issuer->username;
		if(isset($pos[$n])){
			unset($pos[$n]);
			$r .= "해제";
		}elseif(!isset($sub[0]) or trim($pr = $sub[0]) == ""){
			$r .= "명령어 /pr <가격>";
		}elseif(!is_numeric($pr)){
			$r .= "가격은 숫자만 가능합니다.";
			break;
		}else{
			if($pr < 1) $pr = 1;
			$pos[$n] = array("Type" => 0,"1" => 0,"2" => 0,"3" => $pr);
			$r .= "지점1을 터치해주세요. \n";
		}
		$this->pos = $pos;
		return $r;
	}
	
	public function onTouch($data){
		$p = $data["player"];
		$n = $p->username;
		$t = $data["target"];
		$pos = $this->pos;
		$result = $this->property->query("SELECT * FROM Property WHERE startX <= {$t->x} AND landX >= {$t->x} AND startZ <= {$t->z} AND landZ >= {$t->z} AND level = '{$t->level->getName()}'")->fetchArray(SQLITE3_ASSOC);
		if(!isset($pos[$n]) and !is_bool($result)){
			if($t->getID() == 323 or $t->getID() == 68 or $t->getID() == 63){
				$r = "[EconoyProperty] ";
				$info = $this->property->query("SELECT * FROM Property WHERE x = {$t->x} AND y = {$t->y} AND z = {$t->z} AND level = '{$t->level->getName()}'")->fetchArray(SQLITE3_ASSOC);
				if(is_bool($info)) goto check;
				if(!isset($this->tap[$n]) or $t->x.":".$t->y.":".$t->z !== $this->tap[$n]){
					$this->tap[$n] = $t->x.":".$t->y.":".$t->z;
					$this->api->schedule(30, array($this, "deleteTap"), $n);
		 			$p->sendChat($r."Are you sure to buy this? Tap again to confirm.");
					return false;
				}
				$can = $this->api->economy->useMoney($p, $info["price"]);
				if(!$can){
					$p->sendChat($r."You don't enough money to buy this property");
					return false;
				}
				EconomyLandAPI::$a->addLand(array(
					"endX" => $info["landX"],
					"endZ" => $info["landZ"],
					"startX" => $info["startX"],
					"startZ" => $info["startZ"],
					"price" => $info["price"],
					"owner" => $n,
					"level" => $info["level"]
				));
				$level = $this->api->level->get($info["level"]);
				if($level instanceof Level){
					$tile = $this->api->tile->get(new Position($info["x"], $info["y"], $info["z"], $level));
					if($tile !== false){
						$this->api->tile->remove($tile->id);
					}	
					$level->setBlock(new Vector3($info["x"], $info["y"], $info["z"]), BlockAPI::get(AIR));
				}
				$this->property->exec("DELETE FROM Property WHERE landNum = {$info["landNum"]}");
				$p->sendChat($r.Has been bought land");
				return false;
			}
			check:
			if($this->api->ban->isOp($n)){
				if($t->x == $result["x"] and $t->y == $result["y"] and $t->z == $result["z"] and $t->level->getName() == $result["level"]){
					if($data["type"] == "break"){
						$this->property->exec("DELETE FROM Property WHERE landNum = $result[landNum]");
						$p->sendChat($r."The property has been removed.");
						return;
					}
				}
			}else{
				$p->sendChat($r.You don't have permissions to edit property.");
				return false;
			}
		}elseif(isset($pos[$n])){
			$n = $n;
			$r = "/» [FastProperty] ";
			$pn * $pos[$n];
			switch($pn["Type"]){
			 	case "0":
					$pn["1"] = array(
						(int)$t->x,
						(int)$t->y,
						(int)$t->z,
						$p->level->getName()
					);
					$pn["2"] = null;
					unset($pn["2"]);
					$r .= "지점2를 터치해주세요.";
					$pn["Type"] = 1;
					$pos[$n] = $pn;
				break;
				case "1":
					$pn["2"] = array(
						(int)$t->x,
						(int)$t->y,
						(int)$t->z,
					);
					$r .= "프로퍼티 생성완료 : $".$pn["3"]);
					$level = $this->api->level->get($pn["1"][3]);
					$p1 = $pn["1"];
					$p2 = $pn["2"];
					if($p1[0] > $p2[0]){
						$temp = $p2[0];
						$p2[0] = $p1[0];
						$p1[0] = $temp;
					}
					if($p1[2] > $p2[2]){
						$temp = $p2[2];
						$p2[2] = $p1[2];
						$p1[2] = $temp;
					}
					$d = $this->property->query("SELECT * FROM Property WHERE (((startX <= $p1[0] AND landX >= $p1[0]) AND (startZ <= $p1[2] AND landZ >= $p1[2])) OR ((startX <= $p2[0] AND landX >= $p2[0]) AND (startZ <= $p1[2] AND landZ >= $p2[2]))) AND level = '$p1[3]'")->fetchArray(SQLITE3_ASSOC);
					if(!is_bool($d)){
						$r .= "땅이 겹칩니다.";
						break;
					}
					$centerx = (int) $p1[0] + round((($p2[0] - $p1[0]) / 2));
					$centerz = (int) $p1[2] + round((($p2[2] - $p1[2]) / 2));
					$x = (int) round(($p2[0] - $p1[0]));
					$z = (int) round(($p2[2] - $p1[2]));
					$y = 0;
					for(; $y < 127; $y++){
						if($level->getBlock(new Vector3($centerx, $y, $centerz))->getID() === AIR){
							break;
						}
					}
					if($y >= 127){
						$y = (int) $p->entity->y;
						$level->setBlock(new Vector3($centerx, $y, $centerz), BlockAPI::get(AIR));
					}
					$price = $pn["3"];
					$level->setBlock(new Vector3($centerx, $y, $centerz), BlockAPI::get(SIGN_POST));
					$info = $this->property->query("SELECT seq FROM sqlite_sequence")->fetchArray(SQLITE3_ASSOC);
					$entity = $this->api->tile->addSign($level, $centerx, $y, $centerz, array(
						"[PROPERTY]", 
						"Price : ".$price,
						"Blocks : ".($x * $z * 128),
						"Property #".($info["seq"])
					));
					$packet = new UpdateBlockPacket;
					$packet->x = $centerx;
					$packet->y = $y;
					$packet->z = $centerz;
					$packet->block = SIGN_POST;
					$packet->meta = 0;
					$this->api->player->broadcastPacket($this->api->player->getAll($level), $packet);
					$entity->data["creator"] = $n;
					$this->api->tile->spawnToAll($entity);
					$this->property->exec("INSERT INTO Property (owner, price, x, y, z, level, startX, startZ, landX, landZ) VALUES ('{$n}', $price, $centerx, $y, $centerz, '$p1[3]', {$p1[0]}, {$p1[2]}, {$p2[0]}, {$p2[2]});");
					unset($pos[$n]);
				break;
			}
			$p->sendChat($r);
			return false;
		}
	}
	
	public function editPropertyData($data){ // Preparing...
		$info = $this->property->query("SELECT * FROM Property WHERE x = {$data["x"]} AND y = {$data["y"]} AND z = {$data["z"]} AND level = '{$data["level"]}'")->fetchArray(SQLITE3_ASSOC);
		if(is_bool($info)) return false;
		$info["owner"] = isset($data["owner"]) ? $data["owner"] : $info["owner"];
		$info["startX"] = isset($data["startX"]) ? $data["startX"] : $info["startX"];
		$info["startZ"] = isset($data["startZ"]) ? $data["startZ"] : $info["startZ"];
		$info["landX"] = isset($data["endX"]) ? $data["endX"] : $info["landX"];
		$info["landZ"] = isset($data["endZ"]) ? $data["endZ"] : $info["landZ"];
		return true;
	}
	
	public function deleteTap($username){
		$this->tap[$username] = null;
		unset($this->tap[$username]);
	}
}

class EconomyPropertyAPI{
	public static $object;
	
	public static function set(EconomyProperty $obj){
		self::$object = $obj;
	}
}