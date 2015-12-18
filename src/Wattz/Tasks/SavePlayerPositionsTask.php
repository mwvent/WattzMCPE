<?php

namespace Wattz\Tasks;
use Wattz\Main;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;


class SavePlayerPositionsTask extends PluginTask {
    private $plugin;
    
    public function __construct($plugin) {
        $this->plugin = $plugin;
        parent::__construct($plugin);
    }

    public function onRun($currentTick){
        $this->plugin->savePlayerPositions($this->plugin->getServer()->getOnlinePlayers());
    }
}
