<?php

/*
__Pet Plugins__
name=Pet
version=0.1.0
author=DeBe
apiversion=12
class=Pet
*/

class Pet implements Plugin{
	private $api;

	public function __construct(ServerAPI $api, $server =false){
		$this->api = $api;
		$this->Pet = array();
		$this->List = array();
		$this->Tap = array();
 	}

	public function init(){
		$this->yml = $this->api->plugin->readYAML($this->api->plugin->createConfig($this,array(
			"Item" => array("ID" => 265, "MT" => 0),
			"UseItem" => array("ID" => 265, "MT" => 0, "CNT" => 1, "MSG" => "철괴"),
			"ReturnItem" => array("ID" => 265, "MT" => 0, "CNT" => 1, "MSG" => "철괴"),
			"Away" => 2,

	 		"Pet" => array(
				"CHICKEN" => "On",
				"COW" => "On",
				"PIG" => "On",
				"SHEEP" => "On",
				"ZOMBIE" => "On",
				"CREEPER" => "On",
				"SKELETON" => "On",
				"SPIDER" => "On",
				"PIGMAN" => "On"
	 		),

	 		"Message" => "On",
			"UseItemMessage" => " [펫] %1 (%2:%3)가 %4개보다 적습니다. 가지고잇는수 : %5개",
			"Pet_TapMessage" => " [펫] 펫을 제거하려면 다시한번 때려주세요.",
			"Pet_ReturnMessage" => " [펫] 펫을 제거하고 %1 (%2:%3)를 %4개 돌려받으셨습니다.",
			"Pet_AlreadyMessage" => " [펫] 펫이 이미있습니다. 제거하려면 펫을 두번 때려주세요.",
			"Pet_AddMessage" => " [펫] 펫을 소환했습니다.",
			"OwnMessage" => " [펫] 이 펫의 주인은 %1님입니다.",
	 	))."config.yml");
	 	$this->ymlSet();
		$AddHandler = array(
			array("player.quit","Remove"),
			array("player.death","Remove"),
 			array("player.action","Add"),
			array("player.block.touch","Add"),
			array("entity.move","Move"),
			array("entity.health.change","Health"),
 		);
		foreach($AddHandler as $ah) $this->api->addHandler($ah[0], array($this,"Pet_".$ah[1]."_Handler"));
	}

	public function Pet_Add_Handler($data){
		$P = $data["player"];
		$I = $P->getSlot($P->slot);
		$yml = $this->yml;
		$Item = $yml["Item"];
 		if($I->getID() == $Item["ID"] and $I->getMetadata() == $Item["MT"]){
			if(isset($this->Pet[$P->eid])){
				if($this->yml["Message"] == "on") $P->sendChat($this->yml["Pet_AlreadyMessage"]);
			}else{
	 			$UseItem = $this->yml["UseItem"];
		 		$cnt = 0;
		 		foreach($P->inventory as $slot => $Ii){
					if($UseItem["ID"] == $Ii->getID() and $UseItem["MT"] == $Ii->getMetadata()){
						$cnt += $Ii->count;
					}
				}
				if($cnt < $UseItem["CNT"]){
				 	if($this->yml["Message"] == "on"){
						$Msg = $this->yml["ItemMessage"];
						$Msg = str_replace("%1", $UseItem["MSG"] , $Msg);
						$Msg = str_replace("%2", $UseItem["ID"] , $Msg);
						$Msg = str_replace("%3", $UseItem["MT"] , $Msg);
						$Msg = str_replace("%4", $UseItem["CNT"] , $Msg);
						$Msg = str_replace("%5", $cnt, $Msg);
						$P->sendChat($Msg);
					}
				}else{
					$Use = $this->yml["UseItem"];
					$P->removeItem($Use["ID"],$Use["MT"],$Use["CNT"]);
					$this->Pet_Add($data["player"]);
				}
			}
		}
	}

	public function Pet_Add($data){
		$List = $this->List;
		$r = array_rand($List);
		$Meta = $List[$r];
		$Pe = $data->entity;
		$e = $this->api->entity->add($Pe->level,ENTITY_MOB,$Meta,array("x" => $Pe->x,"y" => $Pe->y+0.1,"z" => $Pe->z));
		$this->api->entity->spawnToAll($e);
		$Vec = new VectorXZ($e->x,$e->z);
		$this->Pet[$Pe->eid] = array("Vec" => $Vec,"SaveVec" => $Vec,"Y" => $e->y, "e" => $e->eid, "Own" => $data->username);
		$this->Tap[$Pe->eid] = 0;
		if($this->yml["Message"] == "on") $data->sendChat($this->yml["Pet_AddMessage"]);
	}

	public function Pet_Remove_Handler($data,$event){
		if(isset($this->Pet[$data->eid])){
			if($event == "player.death") $data = $data["player"];
			$this->Pet_Remove($data);
		}
	}

	public function Pet_Remove($data){
		$this->api->entity->get($this->Pet[$data->eid]["e"])->close();
		$Re = $this->yml["ReturnItem"];
		$data->addItem($Re["ID"],$Re["MT"],$Re["CNT"]);
		if($this->yml["Message"] == "on"){
			$Msg = $this->yml["Pet_ReturnMessage"];
			$Msg = str_replace("%1", $Re["MSG"] , $Msg);
			$Msg = str_replace("%2", $Re["ID"] , $Msg);
			$Msg = str_replace("%3", $Re["MT"] , $Msg);
			$Msg = str_replace("%4", $Re["CNT"] , $Msg);
			$data->sendChat($Msg);
		}
		unset($this->Pet[$data->eid]);
 	}


	public function Pet_Move_Handler($data){
		if($data->class == ENTITY_PLAYER){
			if(isset($this->Pet[$data->eid])){
				$Away = $this->yml["Away"];
				$Pet = $this->Pet[$data->eid];
 				$PlayerVec = new VectorXZ($data->x,$data->z);
	  		$PetVec = $Pet["Vec"];
	  		$VecDis = $PlayerVec->distance($PetVec);
				$Target = $PetVec;
				$Y = $Pet["Y"];
 				if($VecDis <= $Away - 0.5){
				 	$this->Pet[$data->eid]["SaveVec"] =	$PlayerVec;
				}elseif($VecDis <= $Away + 0.5){
				 	$Target = $Pet["SaveVec"];
				 	$Y = $data->y;
				}else{
 				 	$Target = $PlayerVec;
				 	$Y = $data->y;
				}
				$this->Pet[$data->eid]["Vec"] = $Target;
				$this->Pet[$data->eid]["Y"] = $Y;
				$Yaw = atan2($PetVec->x - $data->x,$PetVec->z - $data->z)/M_PI*-180 -180;
				$e = $this->api->entity->get($this->Pet[$data->eid]["e"]);
				switch($e->type){
					case MOB_SPIDER:
						$pitch = -30;
					break;
					case MOB_CHICKEN:
						$pitch = 30;
					break;
					default:
					 	$pitch = 0;
					break;
				}
				$pk = new MovePlayerPacket;
				$pk->yaw = $Yaw;
				$e->yaw = $Yaw;
				$pk->bodyYaw = $Yaw;
				$e->bodyYaw = $Yaw;
				$pk->pitch = 0 + $pitch;
				$e->pitch = 0 + $pitch;
				$pk->metadata = $data->getMetadata();		
				$e->metadata = $data->getMetadata();		
				$pk->eid = $Pet["e"];
				$e->x = $Target->x;
				$e->y = $Y +0.1;
				$e->z = $Target->z;
				foreach($this->api->player->getAll() as $Player){
					if($data->level == $Player->entity->level){
						$pk->x = $Target->x;
						$pk->y = $Y +0.1;
						$pk->z = $Target->z;
				 		$Player->dataPacket($pk);
					}else{
						$pk->x = -256;
						$pk->y = 256;
						$pk->z = -256;
						$Player->dataPacket($pk);
					}
				}
		 	}
		}
	}

	public function Pet_Health_Handler($data){
		$e = $data["entity"];
		foreach($this->Pet as $Pet){
			if($e->eid == $Pet["e"]){
				$Own = $Pet["Own"];
				break;
			}
		}
		if(isset($Own)){
			$c = $data["cause"];
			if(is_numeric($data["cause"])){
				$e = $this->api->entity->get($data["cause"]);
				if($e instanceof Entity){
					if($e->player instanceof Player){
						$P = $e->player;
						if($Own == $P->username){
							$Tap = time(true) - $this->Tap[$e->eid];
							if($Tap > 0){
								if($this->yml["Message"] == "on") $P->sendChat($this->yml["Pet_TapMessage"]);
								$this->Tap[$e->eid] = time(true) + 1.5;
							}else{
								$this->Pet_Remove($P);
							}
						}else{
				 			if($this->yml["Message"] == "on"){
								$Msg = $this->yml["Pet_OwnMessage"];
								$Msg = str_replace("%1",$Own, $Msg);
								$P->sendChat($Msg);
							}
						}
					}
				}
			}
		return false;
		}
	}

	public function ymlSet(){
		$Pet = $this->yml["Pet"];
		if(strtolower($Pet["CHICKEN"]) == "on") $this->List[] = MOB_CHICKEN;
		if(strtolower($Pet["COW"]) == "on") $this->List[] = MOB_COW;
	 	if(strtolower($Pet["PIG"]) == "on") $this->List[] = MOB_PIG;
		if(strtolower($Pet["SHEEP"]) == "on") $this->List[] = MOB_SHEEP;
		if(strtolower($Pet["CREEPER"]) == "on") $this->List[] = MOB_CREEPER;
		if(strtolower($Pet["ZOMBIE"]) == "on") $this->List[] = MOB_ZOMBIE;
		if(strtolower($Pet["SKELETON"]) == "on") $this->List[] = MOB_SKELETON;
		if(strtolower($Pet["SPIDER"]) == "on") $this->List[] = MOB_SPIDER;
		if(strtolower($Pet["PIGMAN"]) == "on") $this->List[] = MOB_PIGMAN;
		$this->yml["Message"] = strtolower($this->yml["Message"]);
 	}

	public function __destruct(){
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