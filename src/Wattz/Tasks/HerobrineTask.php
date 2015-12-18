<?php

namespace Wattz\Tasks;
use Wattz\Main;
use Wattz\Entities\Herobrine;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
// for herobrine
use pocketmine\level\format\mcregion\Chunk;
use pocketmine\nbt\tag\Compound;
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
        // clean up herobrines_bats because sometimes their update function stops getting called?
        foreach( $this->herobrines_bats as $batkey => $bat ) {
	    $bat->lifeTime--;
	    if( $bat->lifeTime < 0 ) {
		$bat->close();
		unset( $this->herobrines_bats[$batkey] );
	    }
        }
        // done allow herobrine to do any further spawning while his despawn entities are flying around
        if( count( $this->herobrines_bats ) > 0 ) {
	    return;
        }
	// ensure herobrine is not active if not requested
	if( ! $this->herobrine_active ) {
	    if( ! is_null($this->herobrine_entity)) {
		$this->herobrine_entity->close();
		$this->herobrine_entity = null;
	    }
	    return;
	}
	if(is_null($this->herobrine_entity)) {
	    $player = $this->find_herobrine_target();
	    if( ! is_null($player) ) {
		Server::getInstance()->getLogger()->info(Main::PREFIX  . "Herobrine targetting " . $player->getName());
		$this->herobrine_entity = new Herobrine(new dummyChunk, new Compound, $player, $this->plugin);
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
