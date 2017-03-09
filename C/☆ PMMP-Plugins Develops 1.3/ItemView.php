<?php
/*
__PocketMine Plugin__
name=Item View
version=0.1.0
author=DeBe
class=Item_View
apiversion=12
*/

class Item_View implements Plugin {
	private $api;

	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->View = array();
		$this->IV = array();
	}

	public function init(){
		$this->api->console->register("iv", "ItemView Command",array($this,"Commander"));
		$Alias = array(
			array("i생성", "iv create"),
			array("i제거", "iv delete"),
			array("i로드", "iv load"),
			array("i리셋", "iv reset"), 	
		);
		foreach($Alias as $A) $this->api->console->alias($A[0],$A[1]);
		$Path = $this->api->plugin->configPath($this);
		$this->api->addHandler("player.block.touch", array($this, "MainHandler"));
		$this->api->addHandler("player.pickup", array($this, "SubHandler"));
		$this->ItemView = new Config($Path."ItemView.yml", CONFIG_YAML);
		$ReadView = $this->api->plugin->readYAML($Path."ItemView.yml");
		if(is_array($ReadView)){
			foreach($ReadView as $V){
				$this->View[] = array("X" => $V["X"], "Y" => $V["Y"], "Z" => $V["Z"], "Level" => $V["Level"], "ID" => $V["ID"], "MT" => $V["MT"]);
			}
		}
		$this->RespawnItem();
		$this->api->schedule(6000,array($this,"RespawnItem"),array(),true,"server.schedule");
	}

	public function Commander($cmd,$params,$issuer,$alias){
		$Pr0 = strtolower($params[0]);
		$Pr1 = strtolower($params[1]);
		$U = $issuer->username;
		if($issuer === "console"){
			return "[IV] 콘솔에서는 사용할수없습니다.";
		}else{
			switch($Pr0){
				case "create":
					$I = BlockAPI::fromString($Pr1);
					if($Pr1 == null){
						return "[IV] /IV Create <아이템ID>";
					}elseif($I->getID() == 0){
						return "[IV] 아이템의 ID를 제대로 입력해주세요.";
					}else{
						$I->count = 0;
						$this->IV[$U] = array("create",$I);
						return "[IV] IV를 생성할곳을 터치해주세요.";
					}
				break;
				
				case "delete":
				 $this->IV[$U] = array("delete");
					return "[IV] 제거할 IV를 터치해주세요.";
				break;
	 			
	 			case "reload":
	 				$this->RespawnItem();
	 				return "[IV] IV 리로드 완료.";
				break;
				
				case "reset":
				 	foreach($this->View as $K => $V){
						$L = $this->api->level->get($V["Level"]);
						$X = $V["X"];	$Y = $V["Y"];	$Z = $V["Z"]; 
						$L->setBlockRaw(new Vector3($X,$Y,$Z),BlockAPI::get(0,0),false);
						unset($this->View[$K]);
						$this->ItemView->setAll($this->View);
						$this->ItemView->save();
						$this->RespawnItem();
					}
					return "[IV] IV 리셋 완료";
				break;

				default:
				 return "[IV] Usage: /IV <Create|Delete|Reload|Reset>";
				break;
			}
		}
	}

	public function MainHandler($data,$event){
		$P = $data["player"];
		$U = $P->username;
		if(isset($this->IV[$U])){
			if($this->IV[$U][0] == "create"){
				$I =$this->IV[$U][1]; $ID = $I->getID(); $MT = $I->getMetadata();
				$T = $data["target"];	$X=$T->x;	$Y=$T->y;	$Z=$T->z;	$Ln=$T->level->getName();
				$Up = $P->level->getBlock(new Vector3($X, $Y+1, $Z))->getID();	$Down = $P->level->getBlock(new Vector3($X, $Y, $Z))->getID();
	 			$TF =false;
				if($Down == 20){
					$Y = $Y;
					$TF = true;
				}elseif($Up == 0){
					$Y = $Y+1;
					$TF = true;
				}else{
					$P->sendChat("[IV] 바닥과 유리에만 사용할수있습니다.");
				}if($TF !== false){
					foreach($this->View as $K => $V){
						if($V["X"] == $X and $V["Y"] == $Y and $V["Z"] == $Z and $V["Level"] == $Ln){
							$L = $this->api->level->get($V["Level"]);
							$X = $V["X"];	$Y = $V["Y"];	$Z = $V["Z"]; 
							$L->setBlockRaw(new Vector3($X,$Y,$Z),BlockAPI::get(0,0),false);
							unset($this->IV[$U]);
							unset($this->View[$K]);
							$this->ItemView->setAll($this->View);
							$this->ItemView->save();
						}
					}
					unset($this->IV[$U]);
					$this->CreateView(array("X" => $X, "Y" => $Y, "Z" => $Z, "Level" => $Ln, "ID" => $ID, "MT" => $MT));
					$P->sendChat("[IV] ".$I->getName()."(".$I->getID().":".$I->getMetadata().")를 소환합니다.");
					$this->RespawnItem();
				}
			}elseif($this->IV[$U][0] == "delete"){
				$T = $data["target"];	$X=$T->x;	$Y=$T->y;	$Z=$T->z;	$Ln=$T->level->getName();
				$Block = $P->level->getBlock(new Vector3($X, $Y, $Z))->getID();
	 			$TF =false;
				if($Block == 20){
					foreach($this->View as $K => $V){
						if($V["X"] == $X and $V["Y"] == $Y and $V["Z"] == $Z and $V["Level"] == $Ln){
							$L = $this->api->level->get($V["Level"]);
							$X = $V["X"];	$Y = $V["Y"];	$Z = $V["Z"]; 
							$L->setBlockRaw(new Vector3($X,$Y,$Z),BlockAPI::get(0,0),false);
							unset($this->IV[$U]);
							unset($this->View[$K]);
							$this->ItemView->setAll($this->View);
							$this->ItemView->save();
							$I = BlockAPI::fromString($V["ID"].":".$V["MT"]);
							$P->sendChat("[IV] ".$I->getName()."(".$V["ID"].":".$V["MT"].")를 제거했습니다.");
							$this->RespawnItem();
						}
					}
				}else{
					$P->sendChat("[IV] 제거할 IV의 유리를 터치해주세요.");
				}
			} return false;
		}
	}

	public function SubHandler($data){
		if($data["entity"]->stack == 0) return false;
	}

	public function CreateView($V){
		$this->View[] = array("X" => $V["X"], "Y" => $V["Y"], "Z" => $V["Z"], "Level" => $V["Level"], "ID" => $V["ID"], "MT" => $V["MT"]);
		$this->ItemView->setAll($this->View);
		$this->ItemView->save();
	}

	public function RespawnItem(){
		foreach($this->api->entity->getAll() as $e){
			if($e->class == ENTITY_ITEM and $e->stack == 0){
				$this->api->entity->remove($e->eid);
			}
		}
		foreach($this->View as $V){
			$I = BlockAPI::fromString($V["ID"].":".$V["MT"]);
			$L = $this->api->level->get($V["Level"]);
			$X = $V["X"];	$Y = $V["Y"];	$Z = $V["Z"]; 
			$I->count = 0;
			$L->setBlockRaw(new Vector3($X,$Y,$Z),BlockAPI::get(20,0),false);
			$D = array(
				"x" => $X + 0.5,
				"y" => $Y + 0.19,
				"z" => $Z + 0.5,
				"level" => $L,
				"item" => $I,
			);
			$this->api->entity->spawnToAll($this->api->entity->add($L, ENTITY_ITEM,$V["ID"],$D));
		}
	}

	public function __destruct(){
		$this->ItemView->setAll($this->View);
		$this->ItemView->save();
		$this->view = array();
	}
}