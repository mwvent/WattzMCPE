<?php

namespace MyPlot;

use MyPlot\MyPlot;
use BuddyChannels\MessageFormatter;
use BuddyChannels\Message;

class ChatMessageFormatter extends MessageFormatter {
    /**
     * @var MyPlot
     */
    private $plugin;
    /**
     * @var BuddyChannels
     */
    private $buddyChannels;
    
    public function __construct(MyPlot $plugin) {
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
        return;
    }
    
    public function formatForChannels(Message $message) {
        if(is_null($message->sender)) {
            return;
        }
        $player = $message->sender;
        $playersPlot = $this->plugin->getPlotByPosition($player->getPosition());
        if($playersPlot->id == -1) {
            return;
        }
        $message->server_name = "Plot " . $playersPlot->id;
    }
}

