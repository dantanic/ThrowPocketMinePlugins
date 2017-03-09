<?php
/*
__PocketMine Plugin__
name=Random Ore
version=0.1.8
author=DeBe
apiversion=1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
class=Random_Ore
*/

class Random_Ore implements Plugin{
  private $api;
  private $server;

 public function __construct(ServerAPI $api, $server = false){
  $this->api = $api;
 }
	
 public function init(){
     console("[RandomOre] Load Plugin Data...");
   $this->Ore= new Config($this->api->plugin->configPath($this)."Ore.yml", CONFIG_YAML,array(
			"Ore" => array(
			  "설명" => array("확률","아이템ID","아이템데미지","갯수1","갯수2              갯수1~갯수2중에서 랜덤으로 지급됩니다.","메세지에 표시될 이름","이해안되시는부분은 아래 기본값보고 이해해주세요 ㅠㅠ"),
			  "A" => array(40,4,0,1,1,"코블스톤"),
			  "B" => array(20,263,0,1,1,"석탄"),
			  "C" => array(10,15,0,1,1,"철원석"),
			  "D" => array(9,14,0,1,1,"금원석"),
			  "E" => array(8,321,0,1,7,"레드스톤"),
			  "F" => array(6,351,11,1,7,"청금석"),
			  "G" => array(3,264,0,1,1,"다이아"),
			  /*확률 및 아이템 추가용*/
			  "H" => array(0,0,0,0,""),
			  "I" => array(0,0,0,0,""),
			  "J" => array(0,0,0,0,""),
			  "K" => array(0,0,0,0,""),
			  "L" => array(0,0,0,0,""),
			  "M" => array(0,0,0,0,""),
			  "N" => array(0,0,0,0,""),
			  "O" => array(0,0,0,0,""),
			  "P" => array(0,0,0,0,""),
			  "Q" => array(0,0,0,0,""),
			  "R" => array(0,0,0,0,""),
			  "S" => array(0,0,0,0,""),
			  "T" => array(0,0,0,0,""),
			  "U" => array(0,0,0,0,""),
			  "V" => array(0,0,0,0,""),
			  "W" => array(0,0,0,0,""),
			  "X" => array(0,0,0,0,""),
			  "Y" => array(0,0,0,0,""),
			  "Z" => array(0,0,0,0,""),
			  ),
			"Setting" => array(
			 // 랜덤아이템 나올 블럭 (블럭ID,블럭데미지)
			 "설명" => array("블럭ID","블럭데미지"),
			  "ID" => array(48,0),
			  ),));
   $this->api->addHandler("player.block.break", array($this,"MainHandler"),123);
   $this->api->addHandler("item.drop", array($this,"SubHandler"),123);
    console("[RandomOre] Load Complete...");
 }

 public function SubHandler($data){
     $Setting = $this->Ore->get("Setting");
     $ID = $Setting["ID"][0];
     $MT = $Setting["ID"][1];
     $I = $data["item"];
   if(($I->getID()==$ID)and($I->getMetadata()==$MT)){
     return false;
   }else{
     return true;
   }
 }
 public function MainHandler($data){
     $St = $this->Ore->get("Setting");
     $ID = $St["ID"][0];
     $MT = $St["ID"][1];
     $T = $data["target"];
   if(($T->getID()==$ID)and($T->getMetadata()==$MT)){
 $Ore = $this->Ore->get("Ore");
   $A=$Ore["A"]; $B=$Ore["B"]; $C=$Ore["C"];
   $D=$Ore["D"]; $E=$Ore["E"]; $F=$Ore["F"];
   $G=$Ore["G"]; $H=$Ore["H"]; $I=$Ore["I"];
   $J=$Ore["J"]; $K=$Ore["K"]; $L=$Ore["L"];   
   $M=$Ore["M"]; $N=$Ore["N"]; $O=$Ore["O"];
   $M=$Ore["M"]; $N=$Ore["N"]; $O=$Ore["O"];
   $P=$Ore["P"]; $Q=$Ore["Q"]; $R=$Ore["R"];
   $S=$Ore["S"]; $T=$Ore["T"]; $U=$Ore["U"];
   $V=$Ore["V"]; $W=$Ore["W"]; $X=$Ore["X"];
   $Y=$Ore["Y"]; $Z=$Ore["Z"];  
 $AA=$A[0]; $BB=$AA+$B[0]; $CC=$BB+$C[0];
 $DD=$CC+$D[0]; $EE=$DD+$E[0]; $FF=$EE+$F[0];
 $GG=$FF+$G[0]; $HH=$GG+$H[0]; $II=$HH+$I[0];
 $JJ=$II+$J[0]; $KK=$JJ+$K[0]; $LL=$KK+$L[0];
 $MM=$LL+$M[0]; $NN=$MM+$M[0]; $OO=$NN+$O[0];
 $PP=$OO+$P[0]; $QQ=$PP+$Q[0]; $RR=$QQ+$R[0];
 $SS=$RR+$M[0]; $TT=$SS+$T[0]; $UU=$TT+$U[0];
 $VV=$UU+$V[0]; $WW=$VV+$W[0]; $XX=$WW+$X[0];
 $YY=$XX+$Y[0]; $ZZ=$YY+$Z[0]; 
   $r = rand(0,$ZZ);
   if($r <= $AA){
     $Dt = $A;
   }elseif($r <= $AA){
     $Dt = $B;
   }elseif($r <= $CC){
     $Dt = $C;
   }elseif($r <= $DD){
     $Dt = $D;
   }elseif($r <= $EE){
     $Dt = $E;
   }elseif($r <= $FF){
     $Dt = $F;
   }elseif($r <= $GG){
     $Dt = $G;
   }elseif($r <= $HH){
     $Dt = $H;
   }elseif($r <= $II){
     $Dt = $I;
   }elseif($r <= $JJ){
     $Dt = $J;
   }elseif($r <= $KK){
     $Dt = $K;
   }elseif($r <= $LL){
     $Dt = $L;
   }elseif($r <= $MM){
     $Dt = $M;
   }elseif($r <= $NN){
     $Dt = $N;
   }elseif($r <= $OO){
     $Dt = $O;
   }elseif($r <= $PP){
     $Dt = $P;
   }elseif($r <= $QQ){
     $Dt = $Q;
   }elseif($r <= $RR){
     $Dt = $R;
   }elseif($r <= $SS){
     $Dt = $S;
   }elseif($r <= $TT){
     $Dt = $T;
   }elseif($r <= $UU){
     $Dt = $U;
   }elseif($r <= $VV){
     $Dt = $V;
   }elseif($r <= $WW){
     $Dt = $W;
   }elseif($r <= $XX){
     $Dt = $X;
   }elseif($r <= $YY){
     $Dt = $Y;
   }else{
     $Dt = $Z;
   }
     $Pl = $data["player"];
     $rDt=rand($Dt[3],$Dt[4]);
   $Pl->addItem($Dt[1],$Dt[2],$rDt);
   $MSG = strtolower($St["MSG"][0]);
     $T = $data["target"];
     $Pl->sendChat("[RandomOre] ".$Dt[5]." (을)를 ".$rDt."개 얻었습니다.");
 }
}

 public function __destruct(){
   console("[RandomOre] Unload Plugin...");
 }
}