<?php
 
/*
__PocketMine Plugin__
name=Fire
version=0.1.0
author=DeBe
class=Fire
apiversion=11,12,13
*/
 
class Fire implements Plugin{
  private $api;
 
 public function __construct(ServerAPI $api, $server = false){
   $this->api = $api;
 }

 public function init(){
   $this->api->addHandler("entity.health.change", array($this, "Handler"),100);
 }

 public function Handler($data,$event){
   switch($data["cause"]){
     case "fire":
         return false;
   }
 }
 
 public function __destruct(){
 }
}



















