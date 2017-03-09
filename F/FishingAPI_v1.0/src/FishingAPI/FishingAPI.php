<?php
namespace FishingAPI;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\level\sound\LaunchSound;
use pocketmine\inventory\BigShapedRecipe;
use pocketmine\nbt\tag\Compound as CompoundTag;
use pocketmine\nbt\tag\Enum as ListTag;
use pocketmine\nbt\tag\Double as DoubleTag;
use pocketmine\nbt\tag\Float as FloatTag;
use FishingAPI\entity\FishingHook;
use FishingAPI\entity\FishItemEntity;
use FishingAPI\item\FishingRod;

class FishingAPI extends PluginBase implements Listener{
	private $fishers = [];

	public function onLoad(){
		Entity::registerEntity(FishingHook::class);
//		Entity::registerEntity(FishItemEntity::class);
		$this->registerItem(346, FishingRod::class);
	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @priority HIGHEST
	 */
	public function onPlayerInteract(PlayerInteractEvent $event){
		if(!$event->isCancelled() && $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_AIR){
			$item = $event->getItem();
			if($item->getID() === 346){
				$player = $event->getPlayer();
				if($this->isFishing($player)){
					$this->getFishingHook($player)->onReel();
				}else{
					$fishingHook = Entity::createEntity("FishingHook", $player->level->getChunk($player->x >> 4, $player->z >> 4), 
						new CompoundTag("", [
							"Pos" => new ListTag("Pos", [
								new DoubleTag("", $player->x),
								new DoubleTag("", $player->y + $player->getEyeHeight()),
								new DoubleTag("", $player->z)
							]),
							"Motion" => new ListTag("Motion", [
								new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
								new DoubleTag("", -sin($player->pitch / 180 * M_PI)),
								new DoubleTag("", cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI))
							]),
							"Rotation" => new ListTag("Rotation", [
								new FloatTag("", 0),
								new FloatTag("", 0)
							])
						]), $player
					);
					if($player->isSurvival()){
						$item->setDamage($item->getDamage() + 1);
						$player->getInventory()->setItemInHand($item);
					}
					if($fishingHook instanceof Projectile){
						$this->getServer()->getPluginManager()->callEvent($ev = new ProjectileLaunchEvent($fishingHook));
						if($ev->isCancelled()){
							$fishingHook->kill();
						}else{
							$player->level->addSound(new LaunchSound($player), $player->getViewers());
						}
					}
					$fishingHook->spawnToAll();
					$this->addFishing($player, $fishingHook);
					$player->setDataFlag(Player::DATA_FLAGS, Player::DATA_FLAG_ACTION, true);
	//				$player->startAction = $this->getServer()->getTick();
				}
			}
  		}
	}

	public function registerItem($id, $class){
		Item::$list[$id] = $class;
		if(!Item::isCreativeItem($item = new $class())){
			Item::addCreativeItem($item);
		}
	}

	public function isFishing(Player $player){
		if(isset($this->fishers[$player->getId()])){
			$fishingHook = $this->fishers[$player->getId()];
			if(!$fishingHook->isAlive() || $fishingHook->getStatus() == FishingHook::STATUS_END){
				$fishingHook->kill();
				unset($this->fishers[$player->getId()]);
			}
		}
		return isset($this->fishers[$player->getId()]);
	}

	public function getFishingHook(Player $player){
		if($this->isFishing($player)){
			return $this->fishers[$player->getId()];
		}		
	}

	public function removeFishingHook(Player $player){
		if($this->isFishing($player)){
			$this->getFishingHook($player)->kill();
			unset($this->fishers[$player->getId()]);
		}
	}

	public function addFishing(Player $player, FishingHook $fishingHook){
		if($this->isFishing($player)){
			$this->getFishingHook($player)->kill();
			unset($this->fishers[$player->getId()]);
		}
		$this->fishers[$player->getId()] = $fishingHook;
	}
}