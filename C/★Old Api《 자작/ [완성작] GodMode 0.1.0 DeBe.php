<?php

/*
_DeBe Plugins__
name=God Mode
version=0.1.0
author=DeBe
apiversion=1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
class=God_Mode
*/

class God_Mode implements Plugin{
	private $api,$queue;
	public function __construct(ServerAPI $api, $server =false){
		$this->api =$api;
	}
	
	public function init(){
		$this->queue = array();
		$this->api->addhandler("entity.health.change", array($this, "MainHandler"));
		$this->api->console->register("God", "God mode", array($this, "Commander"));
	}
	
	public function MainHandler($data){
	    $en = $data["entity"];
	  if(!$en->player instanceof Player){return;}
		   $U = $this->api->player->getByEID($data['entity']->eid)->username;
		if(isset($this->queue[$U])){
		  return false;
		}else{
		 return true;
		}
	}

	public function Commander($cmd,$args,$issuer, $alias){
		$m ="[GodMode] ";
		if($issuer === "console"){
			$m.= "게임내에서만 사용해주세요..";
		}elseif(isset($this->queue[$issuer->username])){
			unset($this->queue[$issuer->username]);
			$m.= "갓모드를 해제합니다.";
		}else{
			$this->queue[$issuer->username] = array();
			$m.= "갓모드를 발동합니다. 끄시려면 명령어를 다시 입력해주세요.";
		}
		return $m;
	}
	public function __destruct(){
	}
}

















