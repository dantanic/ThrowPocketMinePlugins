<?php

/*
__PocketMine Plugin__
name=Health Master
version=0.1.3
author=DeBe
apiversion=1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
class=Health_Master
*/

class Health_Master implements Plugin{
private $api, $server;
 public function __construct(ServerAPI $api,$server = false){$this->api = $api;}

 public function init() {
  $this->api->console->register("health", " /health <set|heal|harm> <player> [amount] ", array($this, "Commander"));
   $this->api->console->alias("hp", "health");
   $this->api->console->alias("hps", "health set");
   $this->api->console->alias("hph", "health heal");
   $this->api->console->alias("hpd", "health harm");
 }

 public function Commander($cmd, $params, $issuer,$alias){
      $P= $player->username;
      $I= $issuer->username;
      $m = "[Health] "
      $Player = $this->api->player->get($params[1]);
      $e = $this->api->entity->get($Player->eid);
  switch(strtolower($params[0])){
   case "set":
    if(!isset($params[1])){
     $m.= "Usage: /health <set|heal|harm> <Player> [Amount]\n";
    }else{ 
      if(($Player->gamemode & 0x01) === 0x01){
        $m.= "$Player is in creative mode.\n";
      }else{
        if(!isset($params[2])){
          $count = 20;
        }else{
          $count = (int).$params[2];
        }
        if(!($e === false or $e->dead === true))
          $e->setHealth($count, "console", false);
          $m.= "Set health ".$count." to ".$P."\n";
          $mp.= "You Set health ".$count." by ".$U."\n";;
          $this->api->chat->sendTo(false,$mp,$p);
      }
    }

   case "heal":
    if(!isset($params[1])){
     $m.= "Usage: /health <set|heal|harm> <Player> [Amount]\n";
    }else{ 
      if(($Player->gamemode & 0x01) === 0x01){
        $m.= "$Player is in creative mode.\n";
      }else{
        if(!isset($params[2])){
          $count = 1;
        }else{
          $count = (int).$params[2];
        }
        if(!($e === false or $e->dead === true))
          $e->setHealth($count, "console", false);
          $m.= "Heal ".$count." to ".$P."\n";
          $mp.= "You heal ".$count." by ".$U."\n";;
          $this->api->chat->sendTo(false,$mp,$p);
      }
    }
    
   case "harm":
    if(!isset($params[1])){
     $m.= "Usage: /health <set|heal|harm> <Player> [Amount]\n";
    }else{ 
      if(($Player->gamemode & 0x01) === 0x01){
        $m.= "$Player is in creative mode.\n";
      }else{
        if(!isset($params[2])){
          $count = 1;
        }else{
          $count = (int).$params[2];
        }
        if(!($e === false or $e->dead === true))
          $e->setHealth($count, "console", false);
          $m.= "Harm ".$count." to ".$P."\n";
          $mp.= "You harm ".$count." by ".$U."\n";;
          $this->api->chat->sendTo(false,$mp,$p);
      }
    }
   default:
    $m.= "Usage: /health <set|heal|harm> <player> [amount]\n";
  }
  return $m;
}
public function __destruct(){}}