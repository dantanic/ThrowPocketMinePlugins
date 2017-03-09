<?php
/*
__DeBe's Plugins__
name=DB_Mine
version=for0.8.1
author=DeBe
apiversion=12
class=DB_Mine
*/

class DB_Mine implements Plugin{
	private $api;

	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->set = array();
		$this->msg = array();
		$this->mine = array();
		$this->drop = array();
 }
	
 public function init(){
		console(" [DB] Mine is Load...");
 		$this->api->addHandler("player.block.break", array($this,"MainHandler"));
		$this->api->addHandler("item.drop", array($this,"SubHandler"));
		$this->api->console->register("mine", " [DB] Mine - OP", array($this, "Commander"));
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
		if($set["Mine"] == "on"){
			$b = BlockAPI::fromString($set["Block"]);
			$t = $data["target"];
			if($t->getID() == $b->getID() and $t->getMetadata() == $b->getMetadata()){
			foreach($t->getDrops($data["item"],$data["player"]) as $drop) $this->drop[] = $drop;
				$mine = $this->mine;
				$drop = $mine[array_rand($mine)];
				$item = BlockAPI::fromString($drop["ID"]);	
				$cnt = explode("~",$drop["Count"]);
				if(isset($cnt[1])){
					$count = rand($cnt[0],$cnt[1]);
				}else{
					$count = $cnt[0];
				}
				$item->count = $count;
				$pos = array(
					"x" => $t->x + 0.5,
					"y" => $t->y + 1.19,
					"z" => $t->z + 0.5,
					"level" => $t->level,
					"item" => $item
				);
				$this->api->entity->spawnToAll($this->api->entity->add($t->level, ENTITY_ITEM,$drop["ID"],$pos));
				if($set["Regen"] == "on") $this->api->schedule(20*$set["Time"],array($this,"Regen"),$t);
			}
 		}
	}

 public function SubHandler($data){
 		$i = $data["item"];
		foreach($this->drop as $k => $v){
			if($i->getID() == $v[0] and $i->getMetadata() == $v[1]){
				unset($this->drop[$k]);
				return false;
			}
		}
 }

	public function Regen($r){
		$r->level->setBlockRaw(new Vector3($r->x,$r->y,$r->z),BlockAPI::get($r->getID(),$r->getMetadata()),false);
	}

	public function ymlSet(){
		$this->path = $this->api->plugin->configPath($this);
		$set = new Config($this->path."Setting.yml", CONFIG_YAML, array(
			"Block" => "48:0",
			"Mine" => "On",
			"Regen" => "On",
			"Time" => 5
	 	));
		$msg = new Config($this->path."Message.yml", CONFIG_YAML, array(
			"Block" => "/[Mine] MineBlock set to %1(%2:%3)",
			"Mine" => "/[Mine] MineMode is set to %1",
			"Regen" => "/[Mine] MineRegen set to %1",
			"Time" => "/[Mine] MineTime set to %1 sec",
			"Load" => "/[Mine] MineSetting is reload"
	 	));
		$mine = new Config($this->path."Mine.yml", CONFIG_YAML, array(
			array("Percent" => 700,"ID" => "4:0", "Count" => "1"),
			array("Percent" => 70,"ID" => "263", "Count" => "1~3"),
			array("Percent" => 50,"ID" => "15:0", "Count" => "1"),
			array("Percent" => 20,"ID" => "331:0", "Count" => "1~7"),
			array("Percent" => 15,"ID" => "14:0", "Count" => "1"),
			array("Percent" => 5,"ID" => "351:4", "Count" => "1~7"),
			array("Percent" => 1,"ID" => "264:0", "Count" => "1")
	 	));
	 	$set = $this->api->plugin->readYAML($this->path."Setting.yml");
	 	$msg = $this->api->plugin->readYAML($this->path."Message.yml");
	 	$mine = $this->api->plugin->readYAML($this->path."Mine.yml");
	 	$this->setYml($set,$msg,$mine);
 	}

 public function setYml($set,$msg,$mine){
 		$set["Mine"] = strtolower($set["Mine"]);
 		$set["Regen"] = strtolower($set["Regen"]);
	 	$this->set = $set;
	 	$this->msg = $msg;
 		$this->mine = array();
 		foreach($mine as $m){	
			for($for=0; $for < $m["Percent"]; $for++) $this->mine[] = $m;
 		}
 	}

 public function __destruct(){
		console(" [DB] Mine is Unload...");
 }
}