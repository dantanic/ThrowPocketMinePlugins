<?php

namespace MoreBlock;

use pocketmine\item\Item;
use pocketmine\block\Block;

use MoreBlock\blocks\NetherPortalBlock;
use MoreBlock\blocks\StonePressurePlate;
use MoreBlock\blocks\WoodenPressurePlate;
use MoreBlock\blocks\LightWeightedPressurePlate;
use MoreBlock\blocks\HeavyWeightedPressurePlate;
use MoreBlock\blocks\RedstoneOre;
use MoreBlock\blocks\GlowingRedstoneOre;
use MoreBlock\blocks\Slab;
use MoreBlock\blocks\DoubleWoodSlab;

class FixBlocks extends \pocketmine\plugin\PluginBase{
	const NETHER_PORTAL = 90;
	const STONE_PRESSURE_PLATE = 70;
	const WOODEN_PRESSURE_PLATE = 72;
	const LIGHT_WEIGHTED_PRESSURE_PLATE = 147;
	const HEAVY_WEIGHTED_PRESSURE_PLATE = 148;

 	public function onLoad(){
 		Block::$list[self::STONE_PRESSURE_PLATE] = StonePressurePlate::class;
 		Block::$list[self::WOODEN_PRESSURE_PLATE] = WoodenPressurePlate::class;
 		Block::$list[self::LIGHT_WEIGHTED_PRESSURE_PLATE] = LightWeightedPressurePlate::class;
 		Block::$list[self::HEAVY_WEIGHTED_PRESSURE_PLATE] = HeavyWeightedPressurePlate::class;
		Block::$list[Block::REDSTONE_ORE] = RedstoneOre::class;
		Block::$list[Block::LIT_REDSTONE_ORE] = LitRedstoneOre::class;
 		Block::$list[self::NETHER_PORTAL] = NetherPortalBlock::class;
 		Item::$list[self::NETHER_PORTAL] = NetherPortalBlock::class;
 		Block::$list[Block::DOUBLE_WOOD_SLAB] = DoubleWoodSlab::class;
 		Block::$list[Block::SLAB] = Slab::class;
		for($data = 0; $data < 16; ++$data){
			Block::$fullList[(self::STONE_PRESSURE_PLATE << 4) | $data] = new StonePressurePlate($data);
	 		Block::$fullList[(self::WOODEN_PRESSURE_PLATE << 4) | $data] = new WoodenPressurePlate($data);
	 		Block::$fullList[(self::LIGHT_WEIGHTED_PRESSURE_PLATE << 4) | $data] = new LightWeightedPressurePlate($data);
	 		Block::$fullList[(self::HEAVY_WEIGHTED_PRESSURE_PLATE <<4) | $data] = new HeavyWeightedPressurePlate($data);
			Block::$fullList[(Block::REDSTONE_ORE << 4) | $data] = new RedstoneOre($data);
			Block::$fullList[(Block::LIT_REDSTONE_ORE << 4) | $data] = new GlowingRedstoneOre($data);
			Block::$fullList[(self::NETHER_PORTAL << 4) | $data] = new NetherPortalBlock($data);
 			Block::$list[(Block::DOUBLE_WOOD_SLAB << 4) | $data] = new DoubleWoodSlab($data);
 			Block::$list[(Block::SLAB << 4) | $data] = new Slab($data);
		}
 		foreach([
 			self::STONE_PRESSURE_PLATE, self::WOODEN_PRESSURE_PLATE, self::LIGHT_WEIGHTED_PRESSURE_PLATE, self::HEAVY_WEIGHTED_PRESSURE_PLATE,
 			self::NETHER_PORTAL, [self::NETHER_PORTAL, 3]] as $id){
			if(!Item::isCreativeItem($item = Item::get(is_array($id) ? $id[0] : $id, is_array($id) ? $id[1] : 0))){
				Item::addCreativeItem($item);
			}
		}
 	}
}