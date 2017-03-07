<?php

namespace Wattz\Tasks;
use Wattz\Main;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;


class AutomaticRebootTask extends PluginTask {
    private $plugin;
    private $starttime;
    
    public function __construct($plugin) {
	$this->starttime = time();
        $this->plugin = $plugin;
        parent::__construct($plugin);
    }

    public function onRun($currentTick) {
	$secondstorestart = 3600;
	if ( time() - $this->starttime > $secondstorestart ) {
		Server::getInstance()->getLogger()->critical(Main::PREFIX  . "Server has been running longer than it should starting reboot");
		Server::getInstance()->shutdown();
	}
	
    }
}
