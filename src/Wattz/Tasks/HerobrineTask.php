<?php

namespace Wattz\Tasks;
use Wattz\Main;
use Wattz\Entities\Herobrine;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
// for herobrine
use pocketmine\level\format\mcregion\Chunk;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\Entity;

class dummyChunk extends Chunk
{
     public function __construct() {
	return true;
     }
}

class HerobrineTask extends PluginTask {

    public $herobrine_entity;
    public $herobrine_active;
    public $herobrines_main_target;
    public $plugin;
    public $herobrines_bats;

    public function __construct($plugin) {
	$this->plugin = $plugin;
	$this->herobrine_active = false;
        $this->herobrines_main_target = null;
        $this->herobrine_entity = null;
        $this->herobrines_bats = array();
	parent::__construct($plugin);
    }

    public function onRun($currentTick){
	if ($this->owner->isDisabled()) {
	    if( ! is_null($this->herobrine_entity)) {
		$this->herobrine_entity->close();
		$this->herobrine_entity = null;
	    }
	    return;
	}
	$this->herobrineUpdate();
    }
    
    public function find_herobrine_target() {
	if($this->herobrines_main_target !==null) {
	    if($this->herobrines_main_target->isConnected()) {
		return $this->herobrines_main_target;
	    } else {
		$this->herobrines_main_target = null;
		Server::getInstance()->getLogger()->info(Main::PREFIX  . "Herobrines manually set target left - turning off HB");
		$this->herobrine_active = false;
		return null;
	    }
	}
	$onlinePlayers = $this->plugin->getServer()->getOnlinePlayers();
	if( count($onlinePlayers) < 1 ) {
	    return null;
	}
	$rand_player_key = array_rand($onlinePlayers, 1);
	return $onlinePlayers[$rand_player_key];
    }
    
    public function herobrineUpdate() {
	// ensure herobrine is not active if not requested
	if( ! $this->herobrine_active ) {
	    if( ! is_null($this->herobrine_entity)) {
		$this->herobrine_entity->close();
		$this->herobrine_entity = null;
	    }
	    return;
	}
	if(is_null($this->herobrine_entity)) {
            // herobrine spawn chance 1 in 120 ticks
            if( \rand(1, 120) != 1 && is_null($this->herobrines_main_target) ) {
                return;
            }
	    $player = $this->find_herobrine_target();
	    if( ! is_null($player) ) {
		Server::getInstance()->getLogger()->info(Main::PREFIX  . "Herobrine targetting " . $player->getName());
		$this->herobrine_entity = new Herobrine(new dummyChunk, new CompoundTag, $player, $this->plugin);
	    }
	    return;
	} else {
	    if(is_null($this->herobrine_entity->targetPlayer)) {
		Server::getInstance()->getLogger()->info(Main::PREFIX  . "Herobrine lost target");
		$this->herobrine_entity = null;
	    }
	}
    }
    
}
