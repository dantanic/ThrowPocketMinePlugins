<?php
 
/*
__PocketMine Plugin__
name=Lava
version=0.1.0
author=DeBe
class=Lava
apiversion=11,12,13
*/
 
class Lava implements Plugin{
  private $api;
 
 public function __construct(ServerAPI $api, $server = false){
   $this->api = $api;
 }

 public function init(){
   $this->api->addHandler("entity.health.change", array($this, "Handler"),100);
 }

 public function Handler($data,$event){
   switch($data["cause"]){
     case "lava":
         return false;
   }
 }
 
 public function __destruct(){
 }
}



















