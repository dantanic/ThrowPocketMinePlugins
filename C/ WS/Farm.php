<?php
/*
__DeBe's Plugins__
name=DB_Farm
version=for0.8.1
author=DeBe
apiversion=12
class=DB_Farm
*/

class DB_Farm implements Plugin{
	private $api;

	public function __construct(ServerAPI $api,$server=false){
		$this->api = $api;
		$this->set = array();
		$this->msg = array();
		$this->farm = array();
	}

	public function init(){
		console(" [DB] Farm is Load...");
		$addHandler = array(
			array("player.block.place","Main"),
 		);
 		foreach($addHandler as $ah) $this->api->addHandler($ah[0], array($this,$ah[1]."Handler"));
		$this->api->console->register("farm", " [DB] Farm - OP", array($this, "Commander"));
		$this->ymlSet();
	}

	public function Commander($cmd,$params,$issuer){
		$set = $this->set;
		$msg = $this->msg;
		$farm = $this->farm;
		$m = $msg["Command"];
		
	}

	public function MainHandler($data){
		$set = $this->set;
		$msg = $this->msg["Farm"];
		$farm = $this->farm;
		$p = $data["player"];
		$i = $data["item"];
		$y = $data["block"]->y;
	 	$w = strtolower($data["block"]->level->getName());
		if($this->api->ban->isOP($p)){
			return true;
		}elseif(in_array($i,$farm)){
			$wl = $set["World"];
			$yl = $set["Y"];
			$sa = $set["Allow"];
			if($sa["World"] !== "on" and !in_array($w,$wl)){
 				$m = str_replace("%1", implode(",", $wl), $msg["World"]);
				$m = str_replace("%2", $w, $m);
			}elseif($sa["Y"] !== "on" and !in_array($y,$yl)){
 				$m = str_replace("%1", implode(",", $yl), $msg["Y"]);
				$m = str_replace("%2", $y, $m);
			}
			if(isset($m)){
				if($msg["On"] == "on") $p->sendChat($m);
				return false;
			}
		}
	}

	public function ymlSet(){
		$this->path = $this->api->plugin->configPath($this);
		$set = new Config($this->path."Setting.yml", CONFIG_YAML, array(
			"On" => "On",
			"World" => array("World","Farm"),
			"Y" => array(1,2,3),
			"Allow" => array(
				"World" => "On",
				"Y" => "On"
			)
	 	));
		$msg = new Config($this->path."Message.yml", CONFIG_YAML, array(
			"On" => "On",
			"Command" => array(
				"World" => array(
					"On" => "/[Farm] Farming world is %1.",
					"Add" => "/[Farm] %1 was added to allow the farming world.",
					"Remove" => "/[Farm] %1 was removed to allow the farming world.",
					"Reset" => "/[Farm] Allow farming world is reset."
				),
				"Y" => array(
					"On" => "/[Farm] Farming Y is %1.",
					"Add" => "/[Farm] %1 was added to allow the farming Y.",
					"Remove" => "/[Farm] %1 was removed to allow the farming Y.",
					"Reset" => "/[Farm] Allow farming Y is reset."
				)
			),
			"Farm" => array(
				"World" => "/[Farm] World: %1.에서만 농사가 가능합니다. W:%2",
				"Y" => "/[Farm] Y: %1 에서만 농사가 가능합니다. Y:%2"
			)
	 	));
		$farm = new Config($this->path."Farm.yml", CONFIG_YAML, array(
		 	"6:0", "39:0", "40:0", "59:0", "81:0", "83:0", "104:0", "105:0",
			"141:0", "142:0", "244:0", "295:0", "338:0", "361:0",
			"362:0", "391:0", "392:0", "393:0", "457:0", "458:0"
		));
	 	$set = $this->api->plugin->readYAML($this->path."Setting.yml");
	 	$msg = $this->api->plugin->readYAML($this->path."Message.yml");
	 	$farm = $this->api->plugin->readYAML($this->path."Farm.yml");
	 	$this->setYml($set,$msg,$farm);
 	}

 public function setYml($set,$msg,$farm){
 		$s = $set;
 		$s["On"] = strtolower($s["On"]);
 		$sa = $s["Allow"];
 		$sa["World"] = strtolower($sa["World"]);
 		$sa["Y"] = strtolower($sa["Y"]);
 		$s["Allow"] = $sa;
 		$msg["On"] = strtolower($msg["On"]);
 		foreach($s["World"] as $w) $s["World"][] = strtolower($w);
 		foreach($farm as $f) $this->farm[] = BlockAPI::fromString($f);
	 	$this->set = $s;
	 	$this->msg = $msg;
 	}

	public function __destruct(){
		console(" [DB] Farm is Unload...");
	}
}