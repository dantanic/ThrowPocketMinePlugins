<?php
namespace AsyncItemEntity\entity;

use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\math\Vector3;
use pocketmine\entity\Item as PMItemEntity;
use pocketmine\event\entity\ItemDespawnEvent;
use pocketmine\event\Timings;
use AsyncItemEntity\task\EntityAsyncTask;
use AsyncItemEntity\task\EntityTask;

class Item extends PMItemEntity{
	protected $pickupDelay = 0;
	protected $gravity = 0.04;
	protected $drag = 0.02;
	protected $isRuningAsync = false;


	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}
		$tickDiff = $currentTick - $this->lastUpdate;
		if($tickDiff <= 0 && !$this->justCreated){
			return true;
		}
		$this->lastUpdate = $currentTick;
		$this->timings->startTiming();
		$hasUpdate = $this->entityBaseTick($tickDiff);
		if($this->isAlive()){
			if($this->pickupDelay > 0 && $this->pickupDelay < 32767){ //Infinite delay
				$this->pickupDelay -= $tickDiff;
				if($this->pickupDelay < 0){
					$this->pickupDelay = 0;
				}
			}
//			if(!$this->isRuningAsync){
				Server::getInstance()->getScheduler()->scheduleAsyncTask(new EntityAsyncTask($this->getID()));
				$this->isRuningAsync = true;
//			}
		}
		$this->timings->stopTiming();
		return $hasUpdate || !$this->onGround || abs($this->motionX) > 0.00001 || abs($this->motionY) > 0.00001 || abs($this->motionZ) > 0.00001;
	}

	public function move($dx, $dy, $dz){
	}

	public function onAsyncRun(){
		$this->motionY -= $this->gravity;
		$dx = $this->motionX;
		$dz = $this->motionZ;
		$dy = $this->motionY;
		if($dx == 0 && $dz == 0 && $dy == 0){
			// return true;
		}elseif($this->keepMovement){
			$this->boundingBox->offset($dx, $dy, $dz);
			Server::getInstance()->getScheduler()->scheduleDelayedTask(new EntityTask($this, [
				$this->temporalVector->setComponents(($this->boundingBox->minX + $this->boundingBox->maxX) / 2, $this->boundingBox->minY, ($this->boundingBox->minZ + $this->boundingBox->maxZ) / 2),
				false,
				false
			]), 1);
			return true;
		}else{
			Timings::$entityMoveTimer->startTiming();
			$this->ySize *= 0.4;
			$movX = $dx;
			$movY = $dy;
			$movZ = $dz;
			$axisalignedbb = clone $this->boundingBox;
			$list = $this->level->getCollisionCubes($this, $this->level->getTickRate() > 1 ? $this->boundingBox->getOffsetBoundingBox($dx, $dy, $dz) : $this->boundingBox->addCoord($dx, $dy, $dz), \false);
			foreach($list as $bb){
				$dy = $bb->calculateYOffset($this->boundingBox, $dy);
			}
			$this->boundingBox->offset(0, $dy, 0);
			$fallingFlag = ($this->onGround || ($dy != $movY && $movY < 0));
			foreach($list as $bb){
				$dx = $bb->calculateXOffset($this->boundingBox, $dx);
			}
			$this->boundingBox->offset($dx, 0, 0);
			foreach($list as $bb){
				$dz = $bb->calculateZOffset($this->boundingBox, $dz);
			}
			$this->boundingBox->offset(0, 0, $dz);
			if($this->stepHeight > 0 && $fallingFlag && $this->ySize < 0.05 && ($movX != $dx || $movZ != $dz)){
				$cx = $dx;
				$cy = $dy;
				$cz = $dz;
				$dx = $movX;
				$dy = $this->stepHeight;
				$dz = $movZ;
				$axisalignedbb1 = clone $this->boundingBox;
				$this->boundingBox->setBB($axisalignedbb);
				$list = $this->level->getCollisionCubes($this, $this->boundingBox->addCoord($dx, $dy, $dz), \false);
				foreach($list as $bb){
					$dy = $bb->calculateYOffset($this->boundingBox, $dy);
				}
				$this->boundingBox->offset(0, $dy, 0);
				foreach($list as $bb){
					$dx = $bb->calculateXOffset($this->boundingBox, $dx);
				}
				$this->boundingBox->offset($dx, 0, 0);
				foreach($list as $bb){
					$dz = $bb->calculateZOffset($this->boundingBox, $dz);
				}
				$this->boundingBox->offset(0, 0, $dz);
				if(($cx ** 2 + $cz ** 2) >= ($dx ** 2 + $dz ** 2)){
					$dx = $cx;
					$dy = $cy;
					$dz = $cz;
					$this->boundingBox->setBB($axisalignedbb1);
				}else{
					$this->ySize += 0.5;
				}
			}
			Server::getInstance()->getScheduler()->scheduleDelayedTask(new EntityTask($this, [
				new Vector3(
					($this->boundingBox->minX + $this->boundingBox->maxX) / 2,
					$this->boundingBox->minY - $this->ySize,
					($this->boundingBox->minZ + $this->boundingBox->maxZ) / 2
				),
				$this->onGround,
				true,
				$movX,
				$movY,
				$movZ,
				$dx,
				$dy,
				$dz,
			]), 0);
			Timings::$entityMoveTimer->stopTiming();
		}
	}

	public function onTaskRun(Vector3 $vec, $onGround, $checkChunks, $movX = null, $movY = null, $movZ = null, $dx = null, $dy = null, $dz = null){
		$this->x = $vec->x;
		$this->y = $vec->y;
		$this->z = $vec->z;
		$this->onGround = $onGround;
		if($checkChunks){
			$this->checkChunks();
			$this->checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz);
			$this->updateFallState($dy, $this->onGround);
			if($movX != $dx){
				$this->motionX = 0;
			}
			if($movY != $dy){
				$this->motionY = 0;
			}
			if($movZ != $dz){
				$this->motionZ = 0;
			}
		}
		$friction = 1 - $this->drag;
		if($this->onGround && (abs($this->motionX) > 0.00001 || abs($this->motionZ) > 0.00001)){
			$friction = $this->getLevel()->getBlock($this->temporalVector->setComponents((int) \floor($this->x), (int) \floor($this->y - 1), (int) \floor($this->z) - 1))->getFrictionFactor() * $friction;
		}
		$this->motionX *= $friction;
		$this->motionY *= 1 - $this->drag;
		$this->motionZ *= $friction;
		if($this->onGround){
			$this->motionY *= -0.5;
		}
		$this->updateMovement();
		if($this->age > 6000){
			$this->server->getPluginManager()->callEvent($ev = new ItemDespawnEvent($this));
			if($ev->isCancelled()){
				$this->age = 0;
			}else{
				$this->kill();
				$hasUpdate = true;
			}
		}
		$this->isRuningAsync = false;
	}
}