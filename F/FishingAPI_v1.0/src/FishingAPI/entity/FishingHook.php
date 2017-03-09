<?php
namespace FishingAPI\entity;

use pocketmine\entity\Projectile;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\level\format\FullChunk;
use pocketmine\level\particle\SplashParticle;
use pocketmine\level\particle\SmokeParticle;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\TakeItemEntityPacket;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Compound as CompoundTag;
use pocketmine\nbt\tag\Enum as ListTag;
use pocketmine\nbt\tag\Byte as ByteTag;
use pocketmine\nbt\tag\String as StringTag;
use pocketmine\nbt\tag\Int as IntTag;
use pocketmine\nbt\tag\Short as ShortTag;
use pocketmine\nbt\tag\Float as FloatTag;
use pocketmine\nbt\tag\Double as DoubleTag;
use pocketmine\nbt\tag\Long as LongTag;

/* ToDo List
 * 
 * 낚시대 내구도 지원 (최대 386)
 `*- 낚시에 성공시 2소모
 `*- 엔티티에 맞춘후 회수시 3소모
 `*- 블럭에 박힌후 회수시 4소모내구도
 * 낚시대 조합법 추가 (실2, 막대기3)
 * 
 * 낚시시에 자연스럽게-주변에 물이 충분히 있는지 확인하도록- 수정
 * 낚시 성공시의 물고기 표시를 자연스럽게-아이템이 플레이어힌테 던져지게- 수정
 * 물고기가 다가오는걸 자연스럽게-좌우로 움직이며 다가오게- 수정
 * 
 * API스럽게 이벤트 추가 : FishingEvent [getPlayer() : Player, getHook() : FishingHook]
 * - 낚시찌 던지는 이벤트는  ProjectileLaunchEvent 그대로 이용
 * - 낚시 종료 : FishingEndEvent [getCause() : Int, setCause(Int)  [Cancel, Hook Death, Hook Quit, Player Death, Player Quit, Plugin, ...]]
 * - 입질 시작 : FishingAttractStartEvent (Cancellable) [getPosition() : Position, setPosition(Position), getSpeed() : Int, setSpeed(Int)]
 * - 물기 성공 : FishingBiteOnEvent (Cancellable)
 * - 낚시 실패 : FishingFailedEvent
 * - 낚시 성공 : FishingSuccessEvent (Cancellable) [getFishs() : array<Item>, setFishs(array<Item>)]

------ 위키 정보 ----
 * 블럭에 박힌것을 회수할경우 내구도가 2배로 줄어듬
 * 다른 아이템을 들어서 회수시에 내구도가 줄어들지않음
 * 34블럭이상 멀어질경우 사라짐
 * 생선이 낚일 확률은 1/300틱으로 20틱 당 1초이므로 평균 15초 당 한 마리씩 낚을 수 있다.
 인챈트 되지 않은 낚싯대는 85% 확률로 물고기(Fish)를 낚고 10%확률로 쓰레기(Junk), 5% 확률로 보물(Treasure)을 낚는다. 낚싯대에 바다의 행운(Luck of the Sea) 인챈트를 했을 때 각 레벨마다 쓰레기 낚을 확률을 2.5% 줄이고 보물을 낚을 확률을 1% 올린다. 미끼(Lure) 인챈트 레벨마다 쓰레기와 보물을 낚을 확률 모두 1%씩 감소한다.
 * 물고기를 낚을경우 경험치 1–6
 
 물고기 85% => [생선60%, 연어25%, 복어13%, 흰동가리2%],

보물 5% => [안장, 연잎, {이름표}, 활, 낚시대, {인첸트북}], //각각 1/6

쓰레기 10% => [활12%, 가죽12%, 가죽부츠12%, 구운생선12%, 뼈12%, 물병12%, 철시덫걸이12%, 낚시대2.4%, 막대기6%, 실6%, 먹물10개1.2%]
 
 # 자연스럽게-는 마닐라마크스럽게- 를 의미합니다.
*/


class FishingHook extends Projectile{
	const NETWORK_ID = 37; //77; //93 = Thunder
	const STATUS_LAUNCH = 0;
	const STATUS_WAIT = 1;
	const STATUS_ATTRACT = 2;
	const STATUS_REEL = 3;
	const STATUS_BLOCK = 4;
	const STATUS_ENTITY = 5;
	const STATUS_END = 6;

	public static $fishProbability = null;

	public $width = 0.25;
	public $length = 0.25;
	public $height = 0.5;
	protected $gravity = 0.03;
	protected $drag = 0.1;

	private $status = self::STATUS_LAUNCH;
	private $attractTick = -1;
	private $attractPos;

	public static $motion = -0x00;

	public static function getFish(Item $item = null){
/*
	물고기 85% => [생선60%, 연어25%, 복어13%, 흰동가리2%],
*/
/*

		$typeRand = mt_rand(1, 100);
		if($typeRand <= 5){ // 5% Treasure
			$treasures = [ // Unit 1/6 -> 1/4 (not support nametag and enchantbook)
				329, // Saddle
				346, // FishingRod
				Item::LEATHER, 
				Item::BOW,
			];
			$item = Item::get($treasures[array_rand($treasures)]);
		}elseif($typeRand <= 15){ // 10% Junk
			$itemRand = mt_rand(1, 83);
			if($itemRand <= 1){ // Ink Sac(10) (1/83)
				$item = Item::get(Item::DYE, 0,10);
			}elseif($itemRand <= 3){ // FishingRod (2/83)
				$item = Item::get(346);
			}elseif($itemRand <= 8){ // Stick (5/83)
				$item = Item::get(346);
			}elseif($itemRand <= 13){ // String 5/83
				$item = Item::get(Item::STRING);
			}elseif($itemRand <= 23){ // 12% 10/83
				$item = Item::get();
			}elseif($itemRand <= 33){ // 12% (10/83)
				$item = Item::get();
			}elseif($itemRand <= 43){ // 12% (10/83)
				$item = Item::get();
			}elseif($itemRand <= 53){ //  12% (10/83)
				$item = Item::get();
			}elseif($itemRand <= 63){ // 12% (10/83)
				$item = Item::get();
			}elseif($itemRand <= 73){ // 12% (10/83)
				$item = Item::get();
			}else{ // 12% (10/83)
				$item = Item::get();
*/
/*
		활, 가죽, 가죽부츠, 구운생선, 뼈, 물병, Rotten Flesh Tripwire Hook 12%
		막대기, string 6%
		낚시대2.4%,
		먹물10개1.2%
*/
/*			
		}else{ // 85% Fishs
			
		}
		if($item->getMaxDurability() !== false){
			$durability = $item getMaxDurability();
			$item->setDamage((int) mt_rand($durability * 0.7, $durability - $durability * 0.01)); // Durability 10% ~ 30%
		}
		return $item;
*/
	}

 	public function __construct(FullChunk $chunk, CompoundTag $nbt, Player $shootingEntity){
		parent::__construct($chunk, $nbt, $shootingEntity);
		self::$motion += 0x01;
		$shootingEntity->sendMessage("§b" . self::$motion);
	}
		
 	protected function initEntity(){
		parent::initEntity();
		if(self::$fishProbability == null){
			self::$fishProbability = [];
			foreach([60, 25, 2, 13] as $key => $persent){
				for($i = 0; $i < $persent; $i++) self::$fishProbability[] = $key;
			}
 		}
	}

	public function saveNBT(){
	}

 	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}elseif(!$this->shootingEntity->isAlive() || $this->shootingEntity->spawned !== true || $this->shootingEntity->getInventory()->getItemInHand()->getID() !== 346){
			$this->kill();
			return false;
		}else{
			$this->timings->startTiming();
			$hasUpdate = parent::onUpdate($currentTick);
			$this->age = 0;
			if($this->isInsideOfWater()){
				$this->gravity = -0.05;
//				$this->motionX = 0;
//				$this->motionY = 0.1;
//				$this->motionZ = 0;
				$hasUpdate = true;
				if($this->status === self::STATUS_LAUNCH){
					$this->status = self::STATUS_WAIT;
				}
			}else{
				$this->gravity = 0.1;
				if($this->status !== self::STATUS_END){
					$this->status = self::STATUS_WAIT;
					$this->attractPos = null;
					$this->attractTick = -1;
				}
				$this->timings->stopTiming();
				return $hasUpdate;
			}
			switch($this->status){
				case self::STATUS_WAIT:
/*
					$startX = (int) $this->x - 1;
					$startZ = (int) $this->z - 1;
					for($x = $startX; $x <= $startX + 2; $x++){
						for($z = $startZ; $z <= $startZ + 2; $z++){
							if(!in_array($this->level->getBlockIdAt($x, $y, $z), [8, 9])){
								break;
							}
						}
					}
*/
					if(mt_rand(1, 300) === 1){
						$this->shootingEntity->sendMessage("입질이다...");
						$this->status = self::STATUS_ATTRACT;
						$this->attractPos = new Vector3($this->x + (mt_rand(-30, 30) * 0.1), $this->y, $this->z + (mt_rand(-30, 30) * 0.1));
					}else 	$this->shootingEntity->sendMessage("안온다...");
				break;
				case self::STATUS_ATTRACT:
					if($this->distance($this->attractPos) <= 0.1){
						$this->shootingEntity->sendMessage("물었다...");
						$this->status = self::STATUS_REEL;
 					}
 					$this->shootingEntity->sendMessage("챔질중..." . $this->distance($this->attractPos));
					$disX = $this->x - $this->attractPos->x;
					$disZ = $this->z - $this->attractPos->z;
					$atan2 = atan2($disZ, $disX);
					$this->attractPos = $this->attractPos->add(new Vector3(
						cos($atan2) * 0.03, 
						0, 
						sin($atan2) * 0.03
					));
					$this->level->addParticle(new SplashParticle($this->attractPos->add(0, 1, 0)));
				break;
				case self::STATUS_REEL:
					$this->attractTick++;
 					if($this->attractTick >= 100){
						$this->status = self::STATUS_WAIT;
						$this->attractPos = null;
						$this->attractTick = -1;
						$this->level->addParticle(new SmokeParticle($this->add(0, 0.5, 0)));
					}else{
						$this->shootingEntity->sendMessage("들어병신아...");
						$this->level->addParticle(new SplashParticle($this));
					}
				break;
			}
			$this->timings->stopTiming();
			return $hasUpdate;
		}
	}

	public function kill(){
		$this->status = self::STATUS_END;
		parent::kill();
	}

	public function onReel(){
		if($this->status === self::STATUS_REEL){
			$itemTag = NBT::putItemHelper(FishingHook::getFish());
			$itemTag->setName("Item");
			$reelFish = Entity::createEntity("Item", $this->level->getChunk($this->x >> 4, $this->z >> 4), 
// 			$reelFish = Entity::createEntity("FishItemEntity", $this->level->getChunk($this->x >> 4, $this->z >> 4), 
				new CompoundTag("", [
					"Pos" => new ListTag("Pos", [
						new DoubleTag("", $this->x),
						new DoubleTag("", $this->y + 3),
						new DoubleTag("", $this->z)
					]),
					"Motion" => new ListTag("Motion", [ 
						new DoubleTag("", 0),
						new DoubleTag("", 0.5),
						new DoubleTag("", 0)
					]),
					"Rotation" => new ListTag("Rotation", [
						new FloatTag("", lcg_value() * 360),
						new FloatTag("", 0)
					]),
					"Health" => new ShortTag("Health", 5),
					"Item" => $itemTag,
					"PickupDelay" => new ShortTag("PickupDelay", 3)
				]), $this
			);
			$disX = $this->shootingEntity->x - $reelFish->x;
			$disZ = $this->shootingEntity->z - $reelFish->z;
			$atan2 = atan2($disZ, $disX);
			$reelFish->spawnToAll();
			$pk = new TakeItemEntityPacket();
			$pk->target = $reelFish->getId();
			$selfPk = clone $pk;
			$selfPk->eid = 0;
			$pk->eid = $this->shootingEntity->getId();
			$this->server->broadcastPacket($this->shootingEntity->getViewers(), $pk);
			$this->shootingEntity->dataPacket($selfPk);
			$this->shootingEntity->getInventory()->addItem($reelFish->getItem());
			$reelFish->kill();
		}
		$this->kill();
	}

	public function getStatus(){
		return $this->status;
	}

	public function spawnTo(\pocketmine\Player $player){
		$pk = new AddEntityPacket();
		$pk->type = FishingHook::NETWORK_ID;
		$pk->eid = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$eid = $this->shootingEntity->getId();
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_INVISIBLE, true);
 		$pk->metadata = $this->dataProperties;
/* 		for($i = 1; $i < 1000; $i++){
 			if(!isset($pk->metadata[$i]))
			$pk->metadata[$i] = [self::DATA_TYPE_LONG, 0];
 		}
*/
		$pk->metadata[self::DATA_SHOOTER_ID] = [self::DATA_TYPE_LONG, 0];
		$pk->metadata[16] = [self::DATA_TYPE_BYTE, self::$motion];
//		$pk->metadata[17] = [2, self::$motion];
		$pk->metadata[self::DATA_SILENT] = [self::DATA_TYPE_BYTE, true];
 		if($this->shootingEntity === $player){
			$pk->metadata[self::DATA_NAMETAG] = [self::DATA_TYPE_STRING, \pocketmine\utils\TextFormat::DARK_BLUE . "•"];
			$pk->metadata[self::DATA_SHOW_NAMETAG] = [self::DATA_TYPE_BYTE, 1];
 		}
 		$player->dataPacket($pk);
 		parent::spawnTo($player);
	}
}