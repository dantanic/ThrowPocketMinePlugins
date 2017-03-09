<?php

/*
__NickName Team__
name=NickName Team
version=1.0.0
author=DeBe
apiversion=12
class=NickName_Team
*/

class NickName_Team implements Plugin{
	private $api;
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->set = array();
		$this->msg = array();
		$this->player = array();
	}

	public function init(){
		$this->Team = array();
		$addHandler = array(
			array("player.spawn","setTeam"),
			array("player.interact","useTeam"),
 		);
 		foreach($addHandler as $ah) $this->api->addHandler($ah[0], array($this,$ah[1]));
 		$this->ymlSet();
	}

	public function setTeam($p){
		$set = $this->set;
		$msg = $this->msg;
		$pla = $this->player;
		$sv = $set["View"];
		$pi = $p->iusername;
		if(!isset($pla[$pi])){
 			if(in_array($pi,$sv["AdminList"])){
 				$pla[$pi] = $this->sv["Name"]["Admin"];
 			}else{
 				$tf = false;
 				foreach($this->set["Team"] as $t){
 					if(strpos($pi,$t["Nick"]) !== false){
 						$pla[$pi] = $t["Name"];
 						$tf = true;
 						break;
 					}
 				}
 				if($tf) $pla[$pi] = $sv["Name"]["View"];
 				console($pla[$pi]);
 			}
 			$this->player = $pla;
			if($msg["On"] == "on"){
		  	$m = str_replace("%1", $pla[$pi], $msg["TeamSet"]);
				$p->sendChat($m);
 			}
 		}
	}

	public function Interact_Handler($data){
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
 		}elseif($p_Team == $t_Team){
			$m = str_replace("%1", $ti, $msg["HitTeam"]);
 		}
		if(isset($m) and $msg["On"] == "on") $pp->sendChat($m);
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
			"ViewHit"  => " [PVP] %1는 관전만 가능합니다."
			"HitTeam"  => " [PVP] %1님은 같은 %2입니다.",
			"HitView"  => " [PVP] %1님은 %2입니다."
		));
		$set = $this->api->plugin->readYAML($this->path."Setting.yml");
		$msg = $this->api->plugin->readYAML($this->path."Message.yml");
		$this->setYml($set,$msg);
 	}

 public function setYml($set,$msg){
 		$this->set = array();
 		foreach($set["Team"] as $t) $this->set["Team"][] = array("Nick"  => strtolower($t["Nick"]), "Name"  => $t["Name"]);
 		$this->set["View"] = array("Name" => $set["View"]["Name"]);
 		foreach($set["View"]["AdminList"] as $a) $this->set["View"]["AdminList"][] = strtolower($a);
 		$this->msg = $msg;
 		$this->msg["On"] = strtolower($this->msg["On"]);
	}

	public function StrToLower(){
		$List = $this->set["View"]["AdminList"];
		$this->set["View"]["AdminList"] = array();
		foreach($List as $Lower) $this->set["View"]["AdminList"][] = strtolower($Lower);
		$List = $this->set["Team"];
		$this->set["Team"] = array();
		foreach($List as $Lower) $this->set["Team"][] = array("Nick" => strtolower($Lower["Nick"]), "Name" => $Lower["Name"]);
		$this->set["Message"] = strtolower($this->set["Message"]);
 	}

	public function __destruct(){
	}
}