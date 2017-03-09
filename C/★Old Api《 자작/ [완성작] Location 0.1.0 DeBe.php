<?php
 
/*
__PocketMine Plugin__
name=Location
version=0.1.0
apiversion=8,9,10,11,12
author=DeBe
class=Location
*/

class Location implements Plugin{
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
     $Player->sendChat(" 》 ID:$Tid:".$Tmeta." X:$Tx Y:$Ty Z:$Tz");
   }
   if($I==280){
     $Player->sendChat("[Location] Player Data");
     $Player->sendChat(" 》 X:$Px Y:$Py Z:$Pz");
   }
 }
 	
 public function __destruct(){
 }
}





















