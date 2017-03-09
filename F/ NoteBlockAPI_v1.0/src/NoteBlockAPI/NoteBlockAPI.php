<?php
namespace NoteBlockAPI;

use pocketmine\plugin\PluginBase;
// use NoteBlockAPI\

class NoteBlockAPI extends PluginBase{
	private static $instance = null;

	/**
	 * for check player listen Mugic
	 * @var array
	 */
	private $players = [];

	public static function getInstance(){
		return self::$instance;
	} 

	public function onLoad(){ // for Test Class
		if(self::$instance === null){
			self::$instance = $this;
		}
	}
}