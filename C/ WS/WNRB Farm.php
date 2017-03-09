<?php

/*
__Decompiled with PMFDecompiler__
name=WNRB Farm
version=0.1.0
author=DeBe
apiversion=12
class=wnrb_Farm
*/

class WNRB_Farm implements Plugin{
	private $api, $config;
	public function __construct(ServerAPI $api,$server=false){
		$this->api = $api;
	}
	public function init(){
		$this->api->addHandler("player.block.place", array($this, "MainHandler"),100);
		$this->Farm = $this->api->plugin->readYAML($this->api->plugin->createConfig($this,array("Y" => array(8), "World" => array("Farm"), "List" => array(6,39,40,59,81,83,104,105,141,142,244,295,338,361,362,391,392,393,457,458), "Message" => "On", "World_Message" => "[Farm] %1 월드에서만 가능합니다. World : %2", "Y_Message" => "[Farm] %1 에서만 농사가 가능합니다. Y : %2"))."config.yml");
	}

	public function MainHandler($data){
	 	$FarmY = $this->Farm["Y"];
		$FarmList = $this->Farm["List"];
		$FarmWorld = $this->Farm["World"];
		$Message = strtolower($this->Farm["Message"]);
		$MessageY = $this->Farm["Y_Message"];
		$MessageWorld = $this->Farm["World_Message"];
		$P = $data["player"];
		$I = $data["item"]->getID();
		$Y = $data["block"]->y;
	 	$World = strtolower($data["block"]->level->getName());
		if($this->api->ban->isOP($P)){
			return true;
		}elseif(in_array($I,$FarmList)){
			if(!in_array($World,$FarmWorld) and !in_array(-1,$FarmWorld)){
 				if($Message == "on"){
 					$World_List = "";
 					foreach($FarmWorld as $w) $World_List .= "$w,";
 					$cm = strrpos($World_List, ",", 0);
 					$World_List = substr($World_List, 0, $cm);
					$MessageWorld = str_replace("%1", $Wolrd_List, $MessageWorld);
					$MessageWolrd = str_replace("%2", $World, $MessageWorld);
 					$P->sendChat($MessageWolrd);
 				}
				return false;
			}elseif(!in_array($Y,$FarmY) and !in_array(-1,$FarmY)){
				if($Message == "on"){
 					$Y_List = "";
 					foreach($FarmY as $y) $Y_List .= "$y,";
 					$cm = strrpos($Y_List, ",", 0);
 					$Y_List = substr($Y_List, 0, $cm);
					$MessageY = str_replace("%1", $Y_List, $MessageY);
					$MessageY = str_replace("%2", $Y, $MessageY);
					$P->sendChat($MessageY);
				}
				return false;
			}
		}
	}

	public function __destruct(){
	}
}