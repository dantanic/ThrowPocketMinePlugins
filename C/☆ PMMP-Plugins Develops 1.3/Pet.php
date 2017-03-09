<?php
/*
__DeBe's Plugins__
name=DB_Pet
version=for0.8.1
author=DeBe
apiversion=12
class=DB_Pet
*/

class DB_Pet implements Plugin{
	private $api;

	public function __construct(ServerAPI $api, $server =false){
		$this->api = $api;
		$this->set = array();
		$this->msg = array();
		$this->player = array();
		$this->list = array();
		$this->pet = array();
		$this->tap = array();
 	}

	public function init(){
		console(" [DB] Pet is Load...");
		$addHandler = array(
			array("player.quit","Remove"),
 			array("player.action","Summon"),
			array("player.block.touch","Summon"),
			array("entity.move","Move"),
			array("entity.health.change","Health"),
 		);
		foreach($addHandler as $ah) $this->api->addHandler($ah[0], array($this,"Pet_".$ah[1]."_Handler"));
		$this->api->console->register("펫", " [DB] Pet - Player", array($this, "Commander"));
		$this->api->console->register("pet", " [DB] Pet - OP", array($this, "Commander"));
	 	$this->ymlSet();
	}

	public function Commander($cmd,$params,$issuer){
		$pla = $this->player;
		if($issuer !== "console")	$pla = $this->player[$issuer->iusername];
		$set = $this->set;
		$msg = $this->msg;
		$m = $msg["Command"];
		switch(strtolower($cmd)){
			case "펫":
				if($issuer == "console") return "[Pet] Please run this command in-game";
				switch(strtolower($params[0])){
					case "distance":
					case "d":
					case "거리":
						if(!isset($params[1])) return "/[Pet] /펫 거리 <Num>";
						if($params[1] < 0){
							$dst = 0;
						}else{
							$dst = $params[1];
						}
						$pla["Distance"] = $dst;
						$m = str_replace("%1",$dst,$m["P_Distance"]);
					break;

					case "message":
					case "m":
					case "메세지":
						if(!isset($params[1])) return "/[Pet] /펫 M <On/Off> or /펫 M M <Message>";
						switch($params[1]){
							case "message":
							case "m":
							case "메세지":
								unset($params[0]); unset($params[0]);
								$ms = implode(" ", $params);
								$pla["Message"]["Own"] = $ms;
							break;
							defualt:
								if($pla["Message"]["On"] == "on"){
									$ms = "Off";
								}else{
									$ms = "On";
								}
								$pla["Message"]["On"] = $ms;
							break;
						}
						$m = str_replace("%1",$ms,$m["P_Pet"]);
					break;

					case "spawn":
					case "summon":
					case "summons":
					case "s":
					case "소환":
					case "추가":
						$this->Pet_Summon($issuer);
						return;
					break;

					case "reversespawn":
					case "reversesummon":
					case "recersesummons":
					case "rs":
					case "r":
					case "역소환":
					case "제거":
						$this->Pet_Remove($issuer);
						return;
					break;

					default:
						return " [Pet] /펫 <거리 | 메세지 | 소환 | 제거 >";
					break;
				}
			break;

			case "pet":
				switch(strtolower($params[0])){	
					case "reload":
					case "r":
					case "load":
					case "l":
						$m = $m["Load"];
 					break;

					case "defualt":
					case "dm":
					case "Message":
					case "m":
						if($msg["Defualt"] == "on"){
							$dft = "Off";
						}else{
							$dft = "On";
						}
						$msg["Defualt"] = $dft;
						$m = str_replace("%1",$dft,$m["Message"]);
					break;

					case "defualtdistance":
					case "dd":
					case "distance":
					case "d":
						if(!isset($params[1])) return "/Peta Distance(D) <Num>";
						if($params[1] < 0){
							$dst = 0;
						}else{
							$dst = $params[1];
						}
						$set["Distance"] = $dst;
						$m = str_replace("%1",$dst,$m["Distance"]);
					break;

					case "item":
					case "i":
						if(!isset($params[1])) return "/Peta Item(I) <ItemID>";
						$set = $this->set;
						$i = BlockAPI::fromString($params[1]);
						$set["Item"] = $i->getID().":".$i->getMetadata();
						$m = str_replace("%1",$i->getName(),$m["Item"]);
						$m = str_replace("%2",$i->getID(),$m);
						$m = str_replace("%3",$i->getMetadata(),$m);
					break;

					case "useitem":
					case "ui":
						if(!isset($params[1]) or !isset($params[2])) return "/[Pet] /Peta UseItem(UI) <ItemID> <Count> <Name>";
						$i = BlockAPI::fromString($params[1]);
						if($params[2] < 0){
							$cnt = (int) 0;
						}else{
							$cnt = (int) $params[2];
						}
						$name = $i->getName();
						if(isset($params[3])) $name = $params[3]; 						
						$set["UseItem"] = array("ID" => $i->getID().":".$i->getMetadata(),"Count" => $cnt,"Name" => $name);
						$m = str_replace("%1",$name,$m["UseItem"]);
						$m = str_replace("%2",$i->getID(),$m);
						$m = str_replace("%3",$i->getMetadata(),$m);
						$m = str_replace("%4",$cnt,$m);
					break;

					case "returnitem":
					case "ri":
						if(!isset($params[1]) or !isset($params[2]) or !isset($params[3])) return "/[Pet] /Peta ReturnItem(RI) <ItemID> <Count> <Name>";
						$i = BlockAPI::fromString($params[1]);
						if($params[2] < 0){
							$cnt = (int) 0;
						}else{
							$cnt = (int) $params[2];
						}
						$name = $i->getName();
						if(isset($params[3])) $name = $params[3]; 					
						$set["ReturnItem"] = array("ID" => $i->getID().":".$i->getMetadata(),"Count" => $cnt,"Name" => $name);
						$m = str_replace("%1",$name,$m["ReturnItem"]);
						$m = str_replace("%2",$i->getID(),$m);
						$m = str_replace("%3",$i->getMetadata(),$m);
						$m = str_replace("%4",$cnt,$m);
					break;

					case "allitem":
					case "ai":
						if(!isset($params[1])) return "/[Pet] /Peta Allitem(AI) <ItemID> <Count> <Name>";
						$i = BlockAPI::fromString($params[1]);
						if($params[2] < 0){
							$cnt = (int) 0;
						}else{
							$cnt = (int) $params[2];
						}
						$name = $i->getName();
						if(isset($params[3])) $name = $params[3]; 						
						$set["Item"] = $i->getID().":".$i->getMetadata();
						$set["UseItem"] = array("ID" => $i->getID().":".$i->getMetadata(),"Count" => $cmt,"Name" => $name);
						$set["ReturnItem"] = array("ID" => $i->getID().":".$i->getMetadata(),"Count" => $cnt,"Name" => $name);
						$m = str_replace("%1",$name,$m["AllItem"]);
						$m = str_replace("%2",$i->getID(),$m);
						$m = str_replace("%3",$i->getMetadata(),$m);
						$m = str_replace("%4",$cnt,$m);
					break;

					default:
						return "/[Pet] /Pet <L|M|D|I|UI|RI|AI>";
					break;
				}
				$this->api->chat->broadcast($m);
			break;
		}
		$this->api->plugin->writeYAML($this->path."Setting.yml",$set);
		$this->api->plugin->writeYAML($this->path."Message.yml",$msg);
		$this->api->plugin->writeYAML($this->path."Player.yml",$pla);
		$this->ymlSet();
	}

	public function Pet_Summon_Handler($data){
		$p = $data["player"];
		$i = $p->getSlot($p->slot);
 		$item = BlockAPI::fromString($this->set["Item"]);
 		if($i->getID() == $item->getID() and $i->getMetadata() == $item->getMetadata()){
			$this->Pet_Summon($p);
		}
	}

	public function Pet_Summon($p){
		$set = $this->set;
		$msg = $this->msg;
		$m = $msg["Pet"];
		$pi = $p->iusername;
		$pla = $this->player;
		if(!isset($pla[$pi])){
			$pla[$pi]["Distance"] = $this->set["Distance"];
			$pla[$pi]["Message"] = array("On" => $msg["Defualt"],"Own" => $m["Own"]);
			$this->api->plugin->writeYAML($this->path."Player.yml",$pla);
			$this->ymlSet();
		}
		$pla = $pla[$pi];
		if(isset($this->pet[$p->eid])){
			$m = $m["Already"];
		}else{
		 	$useItem = $set["UseItem"];
			$i = BlockAPI::fromString($useItem["ID"]);
			$cnt = 0;
			foreach($p->inventory as $slot => $ii){
 				$i->count = $ii->count;
 				if($i == $ii) $cnt += $i->count;
				if($cnt >= $useItem["Count"]) break;
			}
			if($cnt < $useItem["Count"]){
				$m = str_replace("%1", $useItem["Name"] , $m["Item"]);
				$m = str_replace("%2", $i->getID() , $m);
				$m = str_replace("%3", $i->getMetadata() , $m);
				$m = str_replace("%4", $useItem["Count"] , $m);
				$m = str_replace("%5", $cnt, $m);
			}else{
				$i = BlockAPI::fromString($useItem["ID"]);
				$p->removeItem($i->getID(),$i->getMetadata(),$useItem["Count"]);
				$list = $this->list;
				$r = array_rand($list);
				$meta = $list[$r];
				$pe = $p->entity;
				$e = $this->api->entity->add($pe->level,ENTITY_MOB,$meta,array("x" => $pe->x,"y" => $pe->y+0.1,"z" => $pe->z));
				$this->api->entity->spawnToAll($e);
				$vec = new VectorXZ($e->x,$e->z);
				$this->pet[$pe->eid] = array("Vec" => $vec, "e" => $e->eid, "Own" => $p->username);
				$this->tap[$pe->eid] = 0;
				$m = $m["Summon"];
			}
		}
		if($pla["Message"]["On"] == "on") $p->sendChat($m);
	}

	public function Pet_Remove_Handler($data){
		$this->Pet_Remove($data);
	}

	public function Pet_Remove($p){
		if(!isset($this->pet[$p->eid])) return;
		$this->api->entity->get($this->pet[$p->eid]["e"])->close();
		$re = $this->set["ReturnItem"];
		$i = BlockAPI::fromString($re["ID"]);
		$p->addItem($i->getID(),$i->getMetadata(),$re["Count"]);
		if($this->player[$p->iusername]["Message"]["On"] == "on"){
			$m = str_replace("%1", $re["Name"] , $this->msg["Pet"]["Return"]);
			$m = str_replace("%2", $i->getID() , $m);
			$m = str_replace("%3", $i->getMetadata() , $m);
			$m = str_replace("%4", $re["Count"] , $m);
			$p->sendChat($m);
		}
		unset($this->pet[$p->eid]);
 	}


	public function Pet_Move_Handler($e){
		if($e->class == ENTITY_PLAYER){
			if(isset($this->pet[$e->eid])){
				$pet = $this->pet[$e->eid];
				$move = $this->getMove($e);
				$target = $move["Target"];
				$yaw = $move["Yaw"];
				$pitch = $move["Pitch"];
 				$this->pet[$e->eid]["Vec"] = $target;
				$pk = new MovePlayerPacket;
				$pk->metadata = $e->getMetadata();		
				$pk->eid = $pet["e"];
				$pk->yaw = $yaw;
				$pk->bodyYaw = $yaw;
				$pk->pitch = $pitch;
				foreach($this->api->player->getAll() as $p){
					if($e->level == $p->entity->level){
						$pk->x = $target->x;
						$pk->y = $target->y;
						$pk->z = $target->z;
				 		$p->dataPacket($pk);
					}else{
						$pk->x = -256;
						$pk->y = 256;
						$pk->z = -256;
						$p->dataPacket($pk);
					}
				}
				$e = $this->api->entity->get($pk->eid);
				$e->x = $pk->x;
				$e->y = $pk->y;
				$e->z = $pk->z;
		 	}
		}
	}

	public function getMove($e){
		$pet = $this->pet[$e->eid];
		$p = $e->player;
		$dis = $this->player[$p->iusername]["Distance"] + 2.5;
 		$playerVec = new VectorXZ($e->x,$e->z);
		$petVec = $pet["Vec"];
		$vecDis = $playerVec->distance($petVec);
		$diffX = $e->x - $petVec->x; 
		$diffZ = $e->z - $petVec->z;
 		if($vecDis >= $dis){
			$target = new VectorXZ($diffX/$dis + $petVec->x,$diffZ/$dis + $petVec->z);
		}elseif($vecDis >= $dis + 5){
 		 	$target = $playerVec;
 		}else{
			$target = $petVec;
		}
		$y = round($e->y+2);
		$cnt = 0;
		while($y){
			$block = $e->level->getBlock(new Vector3($petVec->x,$y-1,$petVec->z))->getID();
			if($block !== 0 or $cnt > 5) break;
			$cnt++;
 	 		$y--;
 		}
		$yaw = atan2($diffX,$diffZ)/M_PI*-180;
 		$diffY = $e->y - $y; 
 		if($this->api->entity->get($pet["e"])->type == 10) $diffY = $y - $e->y; 
		$diffXZ = sqrt(pow($diffX,2) + pow($diffZ,2) + 0.1);
		$pitch = atan($diffY/$diffXZ)/M_PI*-180 *0.8;
		return array("Target" => new Vector3($target->x,$y+0.1,$target->z), "Yaw" => $yaw, "Pitch" => $pitch);
	}

	public function Pet_Health_Handler($data){
		$e = $data["entity"];
		foreach($this->pet as $pet){
			if($e->eid == $pet["e"]){
				$own = $pet["Own"];
				break;
			}
		}
		if(isset($own)){
			$c = $data["cause"];
			if(is_numeric($c)){
				$e = $this->api->entity->get($c);
				if($e instanceof Entity){
					if($e->player instanceof Player){
						$p = $e->player;
						$m = $this->msg["Pet"];
						$pla = $this->player[$p->iusername]["Message"];
						if($own == $p->username){
							$tap = time(true) - $this->tap[$e->eid];
							if($tap > 0){
								$m = $m["Tap"];
								$this->tap[$e->eid] = time(true) + 1.5;
							}else{
								$this->Pet_Remove($p);
								return;
							}
						}else{
							$m = str_replace("%1",$own, $m["Own"]);
						}
						if($pla["On"] == "on") $p->sendChat($m);
					} 
				}
			}
		return false;
		}
	}

	public function ymlSet(){
		$this->path = $this->api->plugin->configPath($this);
		$set = new Config($this->path."Setting.yml", CONFIG_YAML, array(
			"Item" => "265:0",
			"UseItem" => array("ID" => "265:0", "Count" => 1, "Name" => "Iron"),
			"ReturnItem" => array("ID" => "265:0", "Count" => 1, "Name" => "Iron"),
			"Distance" => 0
	 	));
		$msg = new Config($this->path."Message.yml", CONFIG_YAML, array(
	 		"Defualt" => "On",
	 		"Command" => array(
				"Pet" => "/[Pet] Pet setting is Reload",
 				"Message" => "/[Pet] Pet set DefualtMessage to %1",
				"Distance" => "/[Pet] Pet set DefualtDistance to %1",
				"Item" => "/[Pet] Pet SummonItem is set to %1(%2:%3)",
				"UseItem" => "/[Pet] Pet UseItem is set to %1(%2:%3) (Count:%4)",
				"ReturnItem" => "/[Pet] Pet ReturnItem is set to %1(%2:%3) (Count:%4)",
				"AllItem" => "/[Pet] Pet AllItem is set to %1(%2:%3) (Count:%4)",
				"P_Distance" => "/[Pet] Pet distance is set to %1 Block",
				"P_Pet" => "/[Pet] Pet Message is set to %1",
				"P_Message" => "/[Pet] Message is %1"
	 		),
	 		"Pet" => array(
				"Item" => " [Pet] %1(%2:%3)이 %4개보다 적습니다. 소유:%5",
				"Tap" => " [Pet] 펫을 한번더 터치하면 펫이 제거됩니다.",
				"Return" => " [Pet] 펫을 제거하고 %1(%2:%3) %4개를 돌려받았습니다.",
				"Already" => " [Pet] 이미 펫이 소환되었습니다.",
				"Summon" => " [Pet] 펫을 소환했습니다.",
				"Own" => " [Pet] 이 펫의 주인은 [%1] 입니다.",
			)
	 	));
		$pet = new Config($this->path."PetList.yml", CONFIG_YAML, array(
			"CHICKEN" => "On",
			"COW" => "On",
			"PIG" => "On",
			"SHEEP" => "On",
			"ZOMBIE" => "On",
			"CREEPER" => "On",
			"SKELETON" => "On",
			"SPIDER" => "On",
			"PIGMAN" => "On"
	 	));
		$pla = new Config($this->path."Player.yml", CONFIG_YAML, array());
	 	$set = $this->api->plugin->readYAML($this->path."Setting.yml");
	 	$msg = $this->api->plugin->readYAML($this->path."Message.yml");
	 	$pet = $this->api->plugin->readYAML($this->path."PetList.yml");
	 	$pla = $this->api->plugin->readYAML($this->path."Player.yml");
	 	$this->setYml($set,$msg,$pet,$pla);
 	}

	public function setYml($set,$msg,$pet,$pla){
	 	$msg["Defualt"] = strtolower($msg["Defualt"]);
		$a = array(
			"CHICKEN" => MOB_CHICKEN,
			"COW" => MOB_COW,
	 		"PIG" => MOB_PIG,
			"SHEEP" => MOB_SHEEP,
			"CREEPER" => MOB_CREEPER,
			"ZOMBIE" => MOB_ZOMBIE,
			"SKELETON" => MOB_SKELETON,
			"SPIDER" => MOB_SPIDER,
			"PIGMAN" => MOB_PIGMAN
		);
 		foreach($pet as $k => $v){
			if(strtolower($v) == "on") $this->list[] = $a[$k];
		}
		foreach($pla as $k => $v) $this->player[$k] = array("Distance" => $v["Distance"], "Message" => array("On" => strtolower($v["Message"]["On"]), "Own" => $v["Message"]["Own"]));
	 	$this->set = $set;
	 	$this->msg = $msg;
	}

	public function __destruct(){
		console(" [DB] Pet is Unload...");
	}
}

class VectorXZ{
	public $x, $z;

	public function __construct($x = 0, $z = 0){
		if($x instanceof Vector3 !== false){
			$z = $x->z;
			$x = $x->x;
		}
		$this->x = $x;
		$this->z = $z;
	}

	public function distance($x = 0, $z = 0){
		if($x instanceof VectorXZ !== false){
			$z = $x->z;
			$x = $x->x;
		}elseif($x instanceof Vector3 !== false){
			$z = $x->z;
			$x = $x->x;
		}
		$x = pow($this->x - $x,2);
		$z = pow($this->z - $z,2);
		return sqrt($x + $z);
	}

	public function __toString(){
		return "VectorXZ(X=".$this->x.",Z=".$this->z.")";
	}
}