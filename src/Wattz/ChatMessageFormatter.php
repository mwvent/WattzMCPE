<?php

namespace Wattz;

use Wattz\Main;
use BuddyChannels\MessageFormatter;
use BuddyChannels\Message;
use pocketmine\Player;

class ChatMessageFormatter extends MessageFormatter {
    /**
     * @var MyPlot
     */
    private $plugin;
    /**
     * @var BuddyChannels
     */
    private $buddyChannels;
    
    public function __construct(\Wattz\Main $plugin) {
        $this->plugin = $plugin;
        $this->buddyChannels = $plugin->getServer()->getPluginManager()->getPlugin("BuddyChannels");
        if(!is_null($this->buddyChannels)) {
            $this->buddyChannels->registerFormatter($this, true);
            $plugin->getLogger()->info("Registered to BuddyChannels");
        } else {
            $plugin->getLogger()->error("Could not join to BuddyChannels");
        }
    }
    
    public function formatUserMessage(Message $message) {	
	if ( strpos( strtolower( $message->originalMessage ), 'herobrine') !== false ) {
		if( $message->sender instanceof Player ) {
			$this->plugin->herobrineTask->herobrine_spawnundead = true;
			$this->plugin->herobrineTask->herobrines_main_target = $message->sender;
			$this->plugin->herobrineTask->herobrine_active = true;
		}
	}
    }
    
    public function formatForChannels(Message $message) {
	return;
    }
}

