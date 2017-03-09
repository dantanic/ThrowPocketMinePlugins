<?php

namespace BlockPet;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat as Color;

class BlockPetEntity extends \pocketmine\entity\FallingSand{
	protected $gravity = 0.04;
	protected $drag = 0.02;
	public $stepHeight = 0.5;
	protected $blockId = 1;
	protected $damage;
	protected $plugin;
	protected $player;
	protected $isFallow = true;
	protected $jumpTick = 0;
	protected $speed = 1;
	protected $distance = 3;

	public function __construct(\pocketmine\level\format\FullChunk $chunk, \pocketmine\nbt\tag\Compound $nbt, BlockPet $plugin, Player $player = null){
		if($player instanceof Player){
			parent::__construct($chunk, $nbt);
			$this->plugin = $player;
			$this->player = $player;
			$player->sendMessage(Color::YELLOW . "[BlockPet] 블럭펫이 소환되었습니다.");
		}
	}

	protected function initEntity(){
		parent::scheduleUpdate();
		$this->setDataProperty(self::DATA_BLOCK_INFO, self::DATA_TYPE_INT, $this->getBlock() | ($this->getDamage() << 8));
	}

	public function getBoundingBox(){
		return new \pocketmine\math\AxisAlignedBB(
			$x = $this->x - $this->width / 2,
			$y = $this->y - $this->stepHeight,
			$z = $this->z - $this->length / 2,
			$x + $this->width,
			$y + $this->height,
			$z + $this->length
		);
	}

	public function attack($damage, EntityDamageEvent $source){
		if($source instanceof \pocketmine\event\entity\EntityDamageByEntityEvent){
			$damager = $source->getDamager();
			$player = $this->player;
			if($player->getName() == $damager->getName()){
				$item = $damager->getInventory()->getItemInHand();
				if($item->isPlaceable()){
					$block = $item->getBlock();
					if(($this->blockId !== $block->getID() || $this->damage !== $block->getDamage())){
						$this->blockId = $block->getID();
						$this->damage = $block->getDamage();
						$this->setDataProperty(self::DATA_BLOCK_INFO, self::DATA_TYPE_INT, $this->getBlock() | ($this->getDamage() << 8));
						$this->respawnToAll();
						$player->sendMessage(Color::YELLOW . "[BlockPet] 블럭이 변경되었습니다.");
					}
				}else{
					$this->isFallow = !$this->isFallow;
					$player->sendMessage("[BlockPet] 블럭펫이 이제 " . ($this->isFallow ? "따라다닙니다." : "따라다니지않습니다."));
				}
			}else{
				$damager->sendMessage(Color::YELLOW . "[BlockPet] 이 블럭펫의 주인은 " . $player->getName() . "님입니다.");
				$player->sendMessage(Color::YELLOW . "[BlockPet] " . $damager->getName() . "님이 블럭펫을 공격하셨습니다.");
			}
		}
	}

	public function onUpdate($currentTick){
		if($this->closed == true || !($this->player instanceof Player) || $this->player->closed || !$this->player->spawned){
			parent::close();
			return false;
		}else{
 			$dis = sqrt(pow($dZ = $this->player->z - $this->z, 2) + pow($dX = $this->player->x - $this->x, 2));
			$boundingBox = clone $this->getBoundingBox();
			$this->timings->startTiming();
			$tickDiff = max(1, $currentTick - $this->lastUpdate);
			$this->lastUpdate = $currentTick;
			$hasUpdate = $this->entityBaseTick($tickDiff);
			if($this->getLevel() !== $this->player->getLevel()){
				$this->teleport($this->player);
			}else{
				$this->onGround = count($this->level->getCollisionBlocks($boundingBox->offset(0, -$this->gravity, 0))) > 0;
				$x = cos($at2 = atan2($dZ, $dX)) * $this->speed;
				$z = sin($at2) * $this->speed;
				$y = 0;
				$boundingBox->offset(0, $this->gravity, 0);
				$isJump = count($this->level->getCollisionBlocks($boundingBox->grow(0, 0, 0)->offset($x, 1, $z))) <= 0;
				if(count($this->level->getCollisionBlocks($boundingBox->grow(0, 0, 0)->offset(0, 0, $z))) > 0){
					$z = 0;
					if($isJump) $y = $this->gravity;
				}
				if(count($this->level->getCollisionBlocks($boundingBox->grow(0, 0, 0)->offset($x, 0, 0))) > 0){
					$x = 0;
					if($isJump) $y = $this->gravity;
				}
				if(!$this->isFallow || $dis < $this->distance){
					$x = 0;
					$z = 0;
				}
				if($this->isFallow && $dis > 20){
					$this->updateMovement();
 					$this->player->sendMessage("[BlockPet] 같이가요 ");
				}else{
					if(!$isJump && $this->player->y > $this->y - ($this->player instanceof Player ? 0.5 : 0)){
						if($this->jumpTick <= 0){
							$this->jumpTick = 40;
						}elseif($this->jumpTick > 36){
							$y = $this->gravity;
						}
					}
					if($this->jumpTick > 0){
						$this->jumpTick--;
					}
					if(($n = floor($this->y) - $this->y) < $this->gravity && $n > 0){
						$y = -$n;
					}
					if($y == 0 && !$this->onGround){
						$y = -$this->gravity;
					}
					$block = $this->level->getBlock($this->add($vec = new Vector3($x, $y, $z)));
					if($block->hasEntityCollision()){
						$block->addVelocityToEntity($this, $vec2 = $vec->add(0, $this->gravity, 0));
						$vec->x = ($vec->x + $vec2->x / 2) / 5;
						$vec->y = ($vec->y + $vec2->y / 2);
						$vec->z = ($vec->z + $vec2->z / 2) / 5;
					}
					if(count($this->level->getCollisionBlocks($boundingBox->offset(0, -0.01, 0))) <= 0){
						$y -= 0.01;
					}
				}
			}
			$this->updateMovement();
		}
		return $hasUpdate or !$this->onGround or abs($this->motionX) > 0.00001 or abs($this->motionY) > 0.00001 or abs($this->motionZ) > 0.00001;
	}

	public function saveNBT(){
	}

	public function spawnTo(Player $player){
		$pk = new \pocketmine\network\protocol\AddEntityPacket();
		$pk->type = self::NETWORK_ID;
		$pk->eid = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);
		parent::spawnTo($player);
	}
}