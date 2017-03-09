<?php
/*
__DeBe's Plugins__
name=DB_Fish
version=for0.8.1
author=DeBe
apiversion=12
class=DB_Fish
*/

class DB_Fish implements Plugin{
	private $api;

	public function __construct(ServerAPI $api, $server =false){
		$this->api = $api;
		$this->set = array();
		$this->msg = array();
		$this->fish = array();
		$this->player = array();
		$this->cool = array();
 	}

	public function init(){
		console(" [DB] Fish is Load...");
		$addHandler = array(
			array("player.block.touch","Main"),
			array("player.action","Main")
 		);
 		foreach($addHandler as $ah) $this->api->addHandler($ah[0], array($this,$ah[1]."Handler"));
		$this->api->console->register("낚시", " [DB] Fish - Player", array($this, "Commander"));
		$this->api->console->register("fish", " [DB] Fish - OP", array($this, "Commander"));
		$this->ymlSet();
	}

	public function Commander($cmd,$params,$issuer){
		$set = $this->set;
		$msg = $this->msg;
		$pla = $this->player;
		$m = $msg["Command"];
		switch(strtolower($cmd)){
			case "낚시":
	 			if($issuer == "console") return " [Fish] Please command run in-game";
		 		switch(strtolower($params[0])){
	 				case "message":
	 				case "m":
	 				case "메세지":
					default:
	 					$ms = $pla[$issuer->iusername]["Message"];
 	 					if($ms == "on"){
							$ms = "Off";
						}else{
							$ms = "On";
						}
	 			 		$pla[$issuer->iusername]["Message"] = $ms;
						$m = str_replace("%1",$ms,$m["P_Message"]);
	 			 	break;
	 			}
	 			$issuer->sendChat($m);
			break;

			case "fish":
		 		switch(strtolower($params[0])){
					case "fish":
					case "f":
					case "on":
					case "off":
						if($set["Fish"] == "on"){
							$fish = "Off";
						}else{
							$fish = "On";
						}
						$set["Fish"] = $fish;
						$m = str_replace("%1",$fish,$m["Fish"]);
					break;

					case "reload":
					case "load":
					case "r":
					case "l":
						$m = $m["Load"];
					break;
					case "message":
					case "m":
						if($msg["Defualt"] == "on"){
							$mdf = "Off";
						}else{
							$mdf = "On";
						}
						$msg["Defualt"] = $mdf;
						$m = str_replace("%1",$mdf,$m["Message"]);
					break;

					case "item":
					case "i":
						if(!isset($params[1])) return "/[Fish] /Fish Item(I) <ItemID>";
						$i = BlockAPI::fromString($params[1]);
						$set["Item"] = array("ID" => $i->getID().":".$i->getMetadata());
						$m = str_replace("%1",$i->getName(),$m["Item"]);
						$m = str_replace("%2",$i->getID(),$m);
						$m = str_replace("%3",$i->getMetadata(),$m);
					break;

					case "useitem":
					case "ui":
						if(!isset($params[1]) or !isset($params[2])) return "/[Fish] /Fish UseItem(UI) <ItemID> <Count> <Name>";
						$i = BlockAPI::fromString($params[1]);
						if($params[2] < 0 or !is_numeric($params[2])){
							$cnt = 0;
						}else{
							$cnt = $params[2];
						}
						$name = $i->getName();
						if(isset($params[3])) $name = $params[3]; 						$set["UseItem"] = array("ID" => $i->getID().":".$i->getMetadata(),"Count" => $cnt,"Name" => $name);
						$m = str_replace("%1",$name,$m["UseItem"]);
						$m = str_replace("%2",$i->getID(),$m);
						$m = str_replace("%3",$i->getMetadata(),$m);
						$m = str_replace("%4",$i->cnt,$m);
					break;

					case "allitem":
					case "ai":
						if(!isset($params[1]) or !isset($params[2]) or !isset($params[3])) return "/[Fish] /Fish AllItem(AI) <ItemID> <Count> <Name>";
						$i = BlockAPI::fromString($params[1]);
						if($params[2] < 0 or !is_numeric($params[2])){
							$cnt = 0;
						}else{
							$cnt = $params[2];
						}
						$name = $i->getName();
						if(isset($params[3])) $name = $params[3];
						$set["Item"] = array("ID" => $i->getID().":".$i->getMetadata());
						$set["UseItem"] = array("ID" => $i->getID().":".$i->getMetadata(),"Count" => $cnt,"Name" => $name);
						$m = str_replace("%1",$name,$m["AllItem"]);
						$m = str_replace("%2",$i->getID(),$m);
						$m = str_replace("%3",$i->getMetadata(),$m);
						$m = str_replace("%4",$cnt,$m);
					break;

					case "cool":
					case "c":
						if(!isset($params[1])) return "/[Fish] /Fish Cool(C) <Num>";
						if($params[1] < 0 or !is_numeric($params[1])){
							$cool = 0;
						}else{
							$cool = $params[1];
						}
						$set["Cool"] = $cool;
						$m = str_replace("%1",$cool,$m["Cool"]);
					break;

					case "time":
					case "t":
						if(!isset($params[1])) return "/[Fish] /Fish Time(T) <Num>";
						if(!isset($params[2])) $params[2] = $params[1];
						if($params[1] < 0 or !is_numeric($params[1])){
							$t1 = 0;
						}else{
							$t1 = $params[1];
						}
						if($params[2] < $t1){
							$t2 = $t1;
						}else{
							$t2 = $params[2];
						}
						$set["Time"] = array($t1,$t2);
						$m = str_replace("%1",$t1,$m["Time"]);
						$m = str_replace("%2",$t2,$m);
					break;

					default:
						return "/[Fish] /FA <F|L|M|I|UI|AI|C|T>";
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

	public function MainHandler($data,$event){
		$set = $this->set;
		$msg = $this->msg["Fishing"];
		$player = $this->player;
		$p = $data["player"];
		$pi = $p->iusername;
		$i = $p->getSlot($p->slot);
 		$item = BlockAPI::fromString($set["Item"]);
 		$item->count = $i->count;
 		if($i == $item){
			if($this->set["Fish"] !== "on"){
				$p->sendChat($msg["Off"]);
				return false;
			}
 			if(!isset($this->cool[$pi])) $this->cool[$pi] = 0;
			if(!isset($player[$pi]["Message"]) or $player[$pi]["Message"] !== "on" and $player[$pi]["Message"] !== "off") $this->player[$pi]["Message"] = $this->msg["Defualt"];
			$this->api->plugin->writeYAML($this->path."Player.yml",$this->player);
			$this->ymlSet();
			$cool = microtime(true) - $this->cool[$pi];
			if($this->cool[$pi] == -1){
				$msg = $msg["Time"];
			}elseif($cool < 0){
				$msg = str_replace("%1", round($cool*-10)/10 , $msg["Cool"]);
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
					$msg = str_replace("%1", $useItem["Name"] , $msg["Item"]);
					$msg = str_replace("%2", $i->getID() , $msg);
					$msg = str_replace("%3", $i->getMetadata(), $msg);
					$msg = str_replace("%4", $useItem["Count"] , $msg);
					$msg = str_replace("%5", $cnt, $msg);
				}else{
					if($this->Check($p) == false){
						$msg = $msg["Failed"];
					}else{
						$this->Wait($p);
 						$item = BlockAPI::fromString($useItem["ID"]);
						$p->removeItem($item->getID(),$item->getMetadata(),$set["UseItem"]["Count"]);
						$msg = $msg["Wait"];
					}
				}
			}
			if($this->player[$pi]["Message"] == "on") $p->sendChat($msg);
		}
	}

 	public function Check($p){
		$e = $p->entity;
		$eY = $e->yaw;
		$eP = $e->pitch;
		$vs = -sin($eY/180 *M_PI);
		$vc = cos($eY/180*M_PI);
		$vt = -sin($eP/180*M_PI);
		$vp = cos($eP/180*M_PI);
		$x = round($e->x); 
		$y = round($e->y)+1; 
		$z = round($e->z);
		$l = $e->level;
		for($f=0; $f<4; ++$f){
			$x += $vs * $vp *2;
			$y += $vt *2;
			$z += $vc * $vp *2;
			if($f >= 4 or $x < 0 or $x > 256 or $y < 0 or $x > 128 or $z < 0 or $x > 256){
				return false;
			}else{
				$array = array(array(-1,-1,-1,),array(-1,-1,0,),array(-1,-1,1,),array(-1,0,-1,),array(-1,0,0,),array(-1,0,1,),array(0,-1,-1,),array(0,-1,0,),array(0,-1,1,),array(0,0,-1,),array(0,0,0,),array(0,0,1,),array(1,-1,-1,),array(1,-1,0,),array(1,-1,1,),array(1,0,-1,),array(1,0,0,),array(1,0,1,));
				$scan = false;
				foreach($array as $a){
					$b = $l->getBlock(new Vector3(round($x)+$a[0],round($y)+$a[1],round($z+$a[2])))->getID();
					if($b == 8 or $b == 9){
						$scan = true;
						break;
					}
				}
				if($scan !== false) return true;
				return false;
			}
		}
	}

	public function Wait($p){
		$pi = $p->iusername;
		$this->cool[$pi] = -1;
		$t = $this->set["Time"];
		$time = rand($t[0],$t[1]);
		$this->api->schedule(20*$time,array($this,"Fishing"),$p);
	}

	public function Fishing($p){
		$pi = $p->iusername;
		$this->cool[$pi] = microtime(true) + $this->set["Cool"];
 		$f = $this->fish;
		$fish = $f[array_rand($f)];
		$item = BlockAPI::fromString($fish["ID"]);
		$cnt = explode("~",$fish["Count"]);
		if(isset($cnt[1])){
			$count = rand($cnt[0],$cnt[1]);
		}else{
			$count = $cnt[0];
		}
		$p->addItem($item->getID(),$item->getMetadata(),$count);
		$msg = str_replace("%1",$fish["Name"],$this->msg["Fishing"]["Fish"]);
		$msg = str_replace("%2",$count,$msg);
		if($this->player[$pi]["Message"] == "on") $p->sendChat($msg);
	}

	public function ymlSet(){
		$this->path = $this->api->plugin->configPath($this);
		$set = new Config($this->path."Setting.yml", CONFIG_YAML, array(
			"Fish" => "On",
			"Item" => "280:0",
			"UseItem" => array("ID" => "280:0", "Count" => "1", "Name" => "막대기"),
			"Cool" => 5,
			"Time" => array(5,7)
		));
		$msg = new Config($this->path."Message.yml", CONFIG_YAML, array(
			"Defualt" => "On",
			"Fishing" => array(
				"Off" => " [낚시] 낚시가 꺼졌습니다.",
				"Item" => " [낚시] %1 (%2:%3)가 %4개보다 적습니다. 가지고잇는수 : %5개",
				"Cool" => " [낚시] %1초만 기다려주세요.",
	 			"Wait" => " [낚시] 낚시대를 던졌습니다. 잠시만 기다려주세요.",
	 			"Time" => " [낚시] 이미 낚시대를 던졌습니다. 잠시만 기다려주세요.",
				"Fish" => " [낚시] %1를 %2개 낚으셨습니다.",
				"Failed" => " [낚시] 너무 멉니다."
			),
			"Command" => array(
				"Fish" => "/[Fish] Fishing is set to %1",
				"Load" => "/[Fish] Fishing setting is reload",
				"Message" => "/[Fish] Fishing DefualtMessage set to %1",
				"Item" => "/[Fish] Fishing Item is set to %1(%2:%3)",
				"UseItem" => "/[Fish] Fishing UseItem is set to %1(%2:%3) (Count:%4)",
				"AllItem" => "/[Fish] Fishing AllItem is set to %1(%2:%3) (Count:%4)",
				"Cool" => "/[Fish] Fishing Cool is set to %1 sec",
				"Time" => "/[Fish] Fishing Time is set to %1~%2 sec",
				"P_Message" => "/[Fish] Fishing Message is set to %1"
			)
		));
		$fish = new Config($this->path."Fish.yml", CONFIG_YAML, array(
			array("Percent" => 30, "ID" => "0:0", "Count" => "0", "Name" => "[투명한] 공기"),
			array("Percent" => 4, "ID" => "270:0", "Count" => "1", "Name" => "[전설의] 나무곡괭이"),
			array("Percent" => 4, "ID" => "271:0", "Count" => "1", "Name" => "[나무꾼의] 나무도끼"),
			array("Percent" => 4, "ID" => "262:0", "Count" => "1~3", "Name" => "[누가쏜] 화살"),
			array("Percent" => 4, "ID" => "301:0", "Count" => "1", "Name" => "[신고버린] 가죽장화"),
			array("Percent" => 3, "ID" => "297:0", "Count" => "1~2", "Name" => "[상디의] 빵"),
			array("Percent" => 3, "ID" => "260:0", "Count" => "1", "Name" => "[나무의] 사과"),
			array("Percent" => 2, "ID" => "365:0", "Count" => "1~2", "Name" => "[잃어버린] 생닭고기"),
			array("Percent" => 2, "ID" => "319:0", "Count" => "1~2", "Name" => "[썩은] 생돼지고기"),
			array("Percent" => 2, "ID" => "363:0", "Count" => "1~2", "Name" => "[소중한] 생소고기"),
			array("Percent" => 1, "ID" => "366:0", "Count" => "1~2", "Name" => "[먹다버린] 치킨"),
			array("Percent" => 1, "ID" => "320:0", "Count" => "1~2", "Name" => "[떨어뜨린] 삽겹살"),
			array("Percent" => 1, "ID" => "364:0", "Count" => "1~2", "Name" => "[비싼] 스테이크")
		));
		$player = new Config($this->path."Player.yml", CONFIG_YAML, array());
		$set = $this->api->plugin->readYAML($this->path."Setting.yml");
		$msg = $this->api->plugin->readYAML($this->path."Message.yml");
		$fish = $this->api->plugin->readYAML($this->path."Fish.yml");
	 	$player = $this->api->plugin->readYAML($this->path."Player.yml");
 	 	$this->setYml($set,$msg,$fish,$player);
 	}

 public function setYml($set,$msg,$fish,$player){
		$set["Fish"] = strtolower($set["Fish"]);
		$msg["Defualt"] = strtolower($msg["Defualt"]);
		$this->set = $set;
		$this->msg = $msg;
 		$this->fish = array();
 		$this->player = array();
 		foreach($fish as $f){	
			for($for=0; $for < $f["Percent"]; $for++) $this->fish[] = $f;
		}
		foreach($player as $k => $v) $this->player[$k] = array("Message" => strtolower($v["Message"]));
	}

	public function __destruct(){
		console(" [DB] Fish is Unload...");
	}
}