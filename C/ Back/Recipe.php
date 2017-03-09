<?php
/*
__Pocketrecipe Plugin__
name=Recipe
version=1.0.0
author=DeBe
class=Recipe
apiversion=12
*/

class Recipe implements Plugin{
	private $api;

	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->recipe = array();
	}

	public function init(){
		$this->api->addHandler("player.craft",array($this,"MainHandler"));
		$this->ymlSet();
		$this->api->console->register("r","",array($this,"Commander"));
	}

	public function Commander($cmd,$params,$issuer){
		$recipe = $this->recipe;
		switch(strtolower($params[0])){
			case "add":
				if(!isset($params[1])) return "/Recipe add <ItemID>";
				$i = BlockAPI::fromString($params[1]);
				if($i->getID() == 0) return " [Recipe] Please check the ItemID";
				if(in_array($i,$recipe)) return " [Recipe] This is already on the List";
				$resipe[] = array($i->getID().":".$i->getMetadata);
				$this->api->plugin->writeYAML($this->path."Recipe.yml",$recipe);
				$this->ymlSet();
				$this->api->chat->broadcast("/ [Recipe] Now can't make ".$i->getName()."(".$i->getID().":".$i->getMetadata().")");
			break;
			case "remove":
				if(!isset($params[1])) return "/Recipe remove <ItemID>";
				$i = BlockAPI::fromString($params[1]);
				if($i->getID() == 0) return " [Recipe] Please check the ItemID";
				if(in_array($i,$recipe)) return " [Recipe] This is not already on the List";
				foreach($recipe as $r1 => $r2){
					if($r2 == $i) unset($recipe[$r1]);
				}
				$this->api->plugin->writeYAML($this->path."Recipe.yml",$recipe);
				$this->ymlSet();
				$this->api->chat->broadcast("/ [Recipe] Now can make ".$i->getName()."(".$i->getID().":".$i->getMetadata().")");
			break;
	 		case "reload":
				$this->ymlSet();
			return " [Recipe] Reload the List";
 			break;
	 		case "reset":
	 			$this->api->plugin->writeYAML($this->path."Recipe.yml",array());
				$this->ymlSet();
				return " [Recipe] Reset the List";
 			break;
			default:
				$r = "";
				$r .= "/ [Recipe] /Recipe add <ItemID> /n";
				$r .= "/ [Recipe] /Recipe remove <ItemID> /n";
				$r .= "/ [Recipe] /Recipe reload /n";
				$r .= "/ [Recipe] /Recipe reset /n";
				return $r;
			break;
		}
	}

	public function MainHandler($data){
		$p = $data["player"];
		if(!$this->api->ban->isOp($p->iusername)){
			$recipe = array(); 		
			foreach($this->recipe["Recipe"] as $r) $recipe[] = BlockAPI::fromString($r);
			foreach($data["craft"] as $c){
				if(in_array($c,$recipe)){
 					$this->api->schedule(30,array($this,"unCraft"),$data);
					$msg = $this->recipe["Message"];
					$msg = str_replace("%1", $i->getName() , $msg);
					$msg = str_replace("%2", $i->getID() , $msg);
					$msg = str_replace("%3", $i->getMetadata() , $msg);
					$p->sendChat($msg);
				}
			}
		}
	}

	public function unCraft($data){
		console(1);
		$p = $data["player"];
		foreach($data["craft"] as $c) $p->removeItem($c->getID(),$c->getMetadata(),$c->count);
		foreach($data["recipe"] as $r) $p->addItem($r->getID(),$r->getMetadata(),$r->count);
	}

	public function ymlSet(){
		$this->path = $this->api->plugin->configPath($this);
		$this->recipe = new Config($this->path."Recipe.yml", CONFIG_YAML, array(
			"Recipe" => array(
				"46:0",
				"259:0"
			),
			"Message" => " [테러방지] %1 (%2:%3) 는 제작금지입니다."
	 	));
		$this->recipe = $this->api->plugin->readYAML($this->path."Recipe.yml");
	}

	public function __destruct(){
	}
}