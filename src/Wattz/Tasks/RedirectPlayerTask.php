<?php

namespace Wattz\Tasks;
use Wattz\Main;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;


class RedirectPlayerTask extends PluginTask {
    private $plugin;
    private $player;
	private $targetWarp;
    
    public function __construct($plugin, $player, $targetWarp = null) {
        $this->plugin = $plugin;
        $this->player = $player;
		$this->targetWarp = $targetWarp;
        parent::__construct($plugin);
        $plugin->getServer()->getScheduler()->scheduleTask($this,2);
    }

    public function onRun($currentTick){
		if( ! is_null ($this->targetWarp) ) {
			$this->plugin->redirect_run($this->player, $this->targetWarp);
		}
		
        if( ! $this->plugin->cfg["warps"]["this_server_is_redirector"] ) {
            return;
        }
        
        $defaultwarp = $this->plugin->cfg["warps"]["default_redirection"];
        
        $playerLastWarp = $this->plugin->db->db_getUserLocation($this->player);
        if( is_null($playerLastWarp) ) {
            $this->plugin->redirect($this->player, $defaultwarp);
            return;
        }
        if($playerLastWarp["warp"] == $this->plugin->cfg["warps"]["this_server_name"]) {
            return;
        }
        
        $this->plugin->redirect($this->player, $playerLastWarp["warp"]);
    }
}
