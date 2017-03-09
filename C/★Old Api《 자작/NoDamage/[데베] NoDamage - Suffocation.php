<?php
 
/*
__PocketMine Plugin__
name=Suffocation
version=0.1.0
author=DeBe
class=Suffocation
apiversion=11,12,13
*/
 
class Suffocation implements Plugin{
  private $api;
 
 public function __construct(ServerAPI $api, $server = false){
   $this->api = $api;
 }

 public function init(){
   $this->api->addHandler("entity.health.change", array($this, "Handler"),100);
 }

 public function Handler($data,$event){
   switch($data["cause"]){
     case "suffocation":
         return false;
   }
 }
 
 public function __destruct(){
 }
}



















