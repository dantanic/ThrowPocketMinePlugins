<?php
/*
__DeBe's Plugins__
name=DB_Team
version=for0.8.1
author=DeBe
apiversion=12
class=DB_Team
*/

class DB_Team implements Plugin{
	private $api;

	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->set = array();
		$this->msg = array();
		$this->player = array();
	}

	public function init(){
		console(" [DB] Team is Load...");
		$addHandler = array(
			array("player.interact","Main"),
			array("player.spawn","Sub")
 		);
 		foreach($addHandler as $ah) $this->api->addHandler($ah[0], array($this,$ah[1]."Handler"));
		$this->api->console->register("team", " [DB] Team - OP", array($this, "Commander"));
		$this->ymlSet();
	}

	public function Commander($cmd,$params,$issuer){
		$set = $this->set;
		$msg = $this->msg;
 	 	switch(strtolower($params[0])){
			case "block":
			case "b":
				if(!isset($params[1])) return "/[Mine] /Mine Block(B) <BlockID>";
				$i = BlockAPI::fromString($params[1]);
				$set["Block"] = $i->getID().":".$i->getMetadata();
				$m = str_replace("%1", $i->getName() , $msg["Block"]);
				$m = str_replace("%2", $i->getID() , $m);
				$m = str_replace("%3", $i->getMetadata() , $m);
			break;
			case "mine":
			case "m":
			case "on":
			case "off":
				if($set["Mine"] == "on"){
					$mine = "Off";
				}else{
					$mine = "On";
				}
				$set["Mine"] = $mine;
				$m = str_replace("%1",$set["Mine"],$msg["Mine"]);
			break;
			case "regen":
			case "r":
				if($set["Regen"] == "on"){
					$reg = "Off";
				}else{
					$reg = "On";
				}
				$set["Regen"] = $reg;
				$m = str_replace("%1",$reg,$msg["Regen"]);
			break;
			case "time":
			case "t":
				if(!isset($params[1])) return "/[Mine] /Mine Time(T) <Time>";
				if($params[1] < 0 or !is_numeric($params[1])){
					$time = 0;
				}else{
					$time = $params[1];
				}
				$set["Time"] = $time;
				$m = str_replace("%1",$time,$msg["Time"]);
			break;
			case "reload":
			case "load":
			case "rl":
			case "l":
	 			$m = $msg["Load"];
			break;
			default:
				return "/[Mine] /Mine <B|M|R|T|L>";
			break;
		}
		$this->api->plugin->writeYAML($this->path."Setting.yml",$set);
		$this->ymlSet();
		$this->api->chat->broadcast($m);
	}

	public function MainHandler($data){
		$set = $this->set;
		$msg = $this->msg;
		$pla = $this->player;
		$sv = $set["View"]["Name"];
		$pe = $data["entity"];
		$pp = $pe->player;
		$pi = $p->iusername;
		$pt = $pla[$pi];
		$te = $data["targetentity"];	
		$tp = $te->player;	
		$ti = $t->iusername;
		$tt = $pla[$ti];
		if($pt == $sv["View"]){
			$m = str_replace("%1", $pla[$pi], $msg["ViewHit"]);
 		}elseif($tt == $sv["Admin"] or $tt == $sv["View"]){
			$m = str_replace("%1", $ti, $msg["HitView"]);
			$m = str_replace("%2", $tt, $m);
 		}elseif($pt == $tt){
			$m = str_replace("%1", $ti, $msg["HitTeam"]);
			$m = str_replace("%2", $tt, $m);
 		}
		if(isset($m) and $msg["On"] == "on") $pp->sendChat($m);
	}

	public function SubHandler($p){
		$set = $this->set;
		$msg = $this->msg;
		$sv = $set["View"];
		$pi = $p->iusername;
		if(!isset($this->player[$pi])){
 			if(in_array($pi,$sv["AdminList"])){
 				$pla = $this->sv["Name"]["Admin"];
 			}else{
 				$tf = false;
 				foreach($this->set["Team"] as $t){
 					if(strpos($pi,$t["Nick"]) !== false){
 						$pla = $t["Name"];
 						$tf = true;
 						break;
 					}
 				}
 				if($tf == false) $pla = $sv["Name"]["View"];
 			}
 			$this->player[$pi] = $pla;
			if($msg["On"] == "on"){
		  	$m = str_replace("%1", $pla, $msg["TeamSet"]);
				$p->sendChat($m);
 			}
 		}
	}

	public function ymlSet(){
		$this->path = $this->api->plugin->configPath($this);
		$set = new Config($this->path."Setting.yml", CONFIG_YAML, array(
			"Team"  =>  array(
				array("Nick"  => "A_", "Name"  => "A팀"),
				array("Nick"  => "B_", "Name"  => "B팀"),
				array("Nick"  => "C_", "Name"  => "C팀")
 			),
 			"View"  => array(
 				"Name" => array("Admin" => "관리자" , "View" => "관전자"),
 				"AdminList" => array("Admin_Ex_1","Admin_Ex_2")
 			)
		));
		$msg = new Config($this->path."Message.yml", CONFIG_YAML, array(
			"On"  => "On",
			"TeamSet"  => " [PVP] 당신은 %1입니다.",
			"ViewHit"  => " [PVP] %1는 관전만 가능합니다.",
			"HitTeam"  => " [PVP] %1님은 같은 %2입니다.",
			"HitView"  => " [PVP] %1님은 %2입니다."
		));
		$set = $this->api->plugin->readYAML($this->path."Setting.yml");
		$msg = $this->api->plugin->readYAML($this->path."Message.yml");
		$this->setYml($set,$msg);
 	}

 public function setYml($set,$msg){
 		$s = array();
 		foreach($set["Team"] as $t) $s["Team"][] = array("Nick"  => strtolower($t["Nick"]), "Name"  => $t["Name"]);
 		$s["View"] = array("Name" => $set["View"]["Name"]);
 		foreach($set["View"]["AdminList"] as $a) $s["View"]["AdminList"][] = strtolower($a);
 		$msg["On"] = strtolower($msg["On"]);
 		$this->set = $s;
 		$this->msg = $msg;
	}

	public function __destruct(){
		console(" [DB] Team is Unload...");
	}
}