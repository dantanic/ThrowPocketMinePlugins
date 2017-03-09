<?php

/*
__DeBe Plugins__
name=BoneMeal
version=0.1.0
author=DeBe
apiversion=11,12,13
class=BoneMeal
*/

class BoneMeal implements Plugin{
	private $api;
	public function __construct(ServerAPI $api,$server=false){
		$this->api= $api;
	}
	public function init(){
		$this->api->addhandler("player.block.touch", array($this,"MainHandler"), 100);
	}
	public function MainHandler(&$data,$event){
		if($data["item"]->getID() == 351 or $data["item"]->getMetadata() == 15){
			$T =$data["target"];
			if(in_array($T->getID(),array(2,6,39,40,59,81,83,104,105,141,142,244))){
				$data["player"]->removeitem(351,15,1);
				;
			}
		}
	}
	public function __destruct(){
	}
}
