<?php
 
/*
__PocketMine Plugin__
name=Burning
version=0.1.0
author=DeBe
class=Burning
apiversion=11,12,13
*/
 
class Burning implements Plugin{
  private $api;
 
 public function __construct(ServerAPI $api, $server = false){
   $this->api = $api;
 }

 public function init(){
   $this->api->addHandler("entity.health.change", array($this, "Handler"),100);
 }

 public function Handler($data,$event){
   switch($data["cause"]){
     case "burning":
         return false;
   }
 }
 
 public function __destruct(){
 }
}



















