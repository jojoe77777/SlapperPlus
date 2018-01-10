<?php

declare(strict_types = 1);

namespace jojoe77777\SlapperPlus;

use jojoe77777\FormAPI\FormAPI;
use jojoe77777\SlapperPlus\commands\SlapperPlusCommand;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener {

    /** @var array */
    public $entityIds = [];
    /** @var array */
    public $editingId = [];

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getCommandMap()->register("slapperplus", new SlapperPlusCommand($this));
    }

    public function getFormAPI() : FormAPI {
        return $this->getServer()->getPluginManager()->getPlugin("FormAPI");
    }

    public function onPlayerQuit(PlayerQuitEvent $ev){
        unset($this->entityIds[$ev->getPlayer()->getName()]);
        unset($this->editingId[$ev->getPlayer()->getName()]);
    }

}
