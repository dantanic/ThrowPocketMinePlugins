<?php

namespace RubyGame;

use pocketmine\scheduler\PluginTask;

class Task extends PluginTask{
	private $callable, $args;

	public function __construct($plugin, callable $callable, array $args = []){
		$this->plugin = $plugin;
		parent::__construct($plugin);
		$this->callable = $callable;
		$this->args = $args;
	}

	public function onRun($currentTick){
		call_user_func_array($this->callable, $this->args);
	}
}