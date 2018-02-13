<?php

declare(strict_types = 1);

namespace jojoe77777\SlapperPlus\commands;

use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\SlapperPlus\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use slapper\entities\SlapperEntity;
use slapper\entities\SlapperHuman;

class SlapperPlusCommand extends PluginCommand {

    const IMAGE_URL = "https://raw.githubusercontent.com/jojoe77777/vanilla-textures/mob-heads/{0}.png";

    /** @var Main */
    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct("slapperplus", $plugin);
        $this->setPermission("slapperplus.command");
        $this->setDescription("Manage Slapper entities with forms");
    }

    public function getPlugin() : Plugin {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$this->testPermission($sender)){
            return true;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage("§a[§bSlapperPlus§a]§6 This command uses forms and can only be executed ingame.");
            return true;
        }
        $this->createMenu()->sendToPlayer($sender);
        return true;
    }

    private function createMenu(){
        $form = $this->plugin->getFormAPI()->createSimpleForm(function (Player $player, int $data = null){
            $selection = $data;
            if($selection === null){
                return; // Closed form
            }
            switch($selection){
                case 0: // "List Slapper entities"
                    $this->createSlapperList($player)->sendToPlayer($player);
                    break;
                case 1: // "Create a new Slapper entity"
                    $this->createSlapperCreationForm($player)->sendToPlayer($player);
                    break;
            }
        });
        $form->setTitle("§aSlapperPlus §6-§b Main menu");
        $form->setContent("");
        $form->addButton("Edit Slapper entities");
        $form->addButton("Create a new Slapper entity");
        return $form;
    }

    private function createSlapperCreationForm(Player $player){
        $form = $this->plugin->getFormAPI()->createCustomForm(function (Player $player, array $data = null){
            if($data === null){
                return;
            }
            $entityType = $data[0];
            $name = $data[1];
            $this->plugin->makeSlapper($player, $entityType, $name);
        });
        $form->setTitle("§bCreate Slapper entity");
        $form->addDropdown("Entity type", Main::ENTITY_LIST, 0);
        $form->addInput("Name", "Name", $player->getName());
        return $form;
    }

    private function createSlapperList(Player $player){
        $form = $this->plugin->getFormAPI()->createSimpleForm(function (Player $player, int $data = null){
            $selection = $data;
            if($selection === null){
                return; // Closed form
            }
            $entityIds = $this->plugin->entityIds[$player->getName()] ?? null;
            if($entityIds === null){
                $player->sendMessage("§a[§bSlapperPlus§a]§6 Invalid form");
                return;
            }
            /** @var int $eid */
            $eid = $entityIds[$selection] ?? null;
            if($eid === null){
                $player->sendMessage("§a[§bSlapperPlus§a]§6 Invalid selection");
                return;
            }
            $entity = $this->plugin->getServer()->findEntity($eid);
            unset($this->plugin->entityIds[$player->getName()]);
            if($entity === null || $entity->isClosed()){
                $player->sendMessage("§a[§bSlapperPlus§a]§6 Invalid entity");
                return;
            }
            $this->plugin->editingId[$player->getName()] = $eid;
            $this->createSlapperDesc($entity)->sendToPlayer($player);
        });
        $form->setTitle("§aSlapper entities (click to edit)");
        $form->setContent("");
        $entityIds = [];
        $i = 0;
        foreach($this->getPlugin()->getServer()->getLevels() as $level){
            foreach($level->getEntities() as $entity){
                if($entity instanceof SlapperEntity){
                    $class = get_class($entity);
                    if(strpos($class, "other") === false){
                        $entityType = substr(get_class($entity), strlen("slapper\\entities\\Slapper"));
                    } else {
                        $entityType = substr(get_class($entity), strlen("slapper\\entities\\other\\Slapper"));
                    }
                    $form->addButton($this->formatSlapperEntity($entity, $entityType), SimpleForm::IMAGE_TYPE_URL, $this->getSlapperIcon($entityType));
                    $entityIds[$i] = $entity->getId();
                    ++$i;
                } elseif($entity instanceof SlapperHuman){
                    $form->addButton($this->formatSlapperHuman($entity), SimpleForm::IMAGE_TYPE_URL, $this->getSlapperIcon("Human"));
                    $entityIds[$i] = $entity->getId();
                    ++$i;
                }
            }
        }
        $this->plugin->entityIds[$player->getName()] = $entityIds;
        return $form;
    }

    private function formatSlapperEntity(SlapperEntity $entity, string $type) : string{
        $name = $this->shortenName($entity->getNameTag());
        $pos = round($entity->getX()) . ", " . round($entity->getY()) . ", " . round($entity->getZ()) . ", " . $entity->getLevel()->getName();
        return "§6\"§b{$name}§6\" §7(§5{$type}§7)\n§1{$pos}";
    }

    private function formatSlapperHuman(SlapperHuman $entity) : string {
        $name = $this->shortenName($entity->getNameTag());
        $pos = round($entity->getX()) . ", " . round($entity->getY()) . ", " . round($entity->getZ()) . ", " . $entity->getLevel()->getName();
        return "§6\"§b{$name}§6\" §7(§5Human§7)\n§1{$pos}";
    }

    private function getSlapperIcon($entityType){
        if($entityType === "Human"){
            return str_replace("{0}", (mt_rand(0, 1) === 0 ? "steve" : "alex"), self::IMAGE_URL);
        }
        return str_replace("{0}", strtolower($entityType), self::IMAGE_URL);
    }

    private function createSlapperDesc(Entity $entity){
        $form = $this->plugin->getFormAPI()->createCustomForm(function (Player $player, array $data = null){
            if($data === null){
                return;
            }
            $eid = $this->plugin->editingId[$player->getName()];
            /** @var Entity $entity */
            $entity = $this->plugin->getServer()->findEntity($eid);
            if($entity === null || $entity->isClosed()){
                return;
            }
            $name = (string) $data[1];
            $yaw = (int) $data[2];
            $pitch = (int) $data[3];
            $teleport = (bool) $data[4];
            $entity->setNameTag($name);
            if($teleport){
                $entity->teleport($player);
                $entity->respawnToAll();
            } else {
                $entity->setRotation($yaw, $pitch);
            }
            $player->sendMessage("§a[§bSlapperPlus§a]§6 Updated entity data");
            unset($this->plugin->editingId[$player->getName()]);
        });
        $form->setTitle("§bEditing {$this->shortenName($entity->getNameTag())}");
        if($entity instanceof SlapperEntity){
            $form->addLabel("Entity type: {$this->getSlapperType($entity)}");
            $form->addInput("Entity name", "Name", $entity->getNameTag());
            $form->addSlider("Yaw", 0, 360, -1, (int) $entity->getYaw());
            $form->addSlider("Pitch", 0, 180, -1, (int) $entity->getPitch());
            $form->addToggle("Teleport here", false);
        } elseif($entity instanceof SlapperHuman){
            $form->addLabel("Entity type: Human");
            $form->addInput("Entity name", "Name", $entity->getNameTag());
            $form->addSlider("Yaw", 0, 360, -1, (int) $entity->getYaw());
            $form->addSlider("Pitch", 0, 180, -1, (int) $entity->getPitch());
            $form->addToggle("Teleport here", false);
        }
        return $form;
    }

    private function shortenName(string $name){
        if(strlen($name) > 16){
            return substr($name, 0, 16) . "...";
        }
        return $name;
    }

    private function getSlapperType(SlapperEntity $entity){
        $class = get_class($entity);
        if(strpos($class, "other") === false){
            return substr(get_class($entity), strlen("slapper\\entities\\Slapper"));
        } else {
            return substr(get_class($entity), strlen("slapper\\entities\\other\\Slapper"));
        }
    }

}
