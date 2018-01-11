<?php

declare(strict_types = 1);

namespace jojoe77777\SlapperPlus;

use jojoe77777\FormAPI\FormAPI;
use jojoe77777\SlapperPlus\commands\SlapperPlusCommand;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use slapper\events\SlapperCreationEvent;

class Main extends PluginBase implements Listener {

    const ENTITY_LIST = [
        "Human", "Boat", "FallingSand", "Minecart", "PrimedTNT",
        "Bat", "Blaze", "CaveSpider", "Chicken", "Cow",
        "Creeper", "Donkey", "ElderGuardian", "Enderman",
        "Endermite", "Evoker", "Ghast", "Guardian", "Horse",
        "Husk", "IronGolem", "LavaSlime", "Llama",
        "Mule", "MushroomCow", "Ocelot", "Pig", "PigZombie",
        "PolarBear", "Rabbit", "Sheep", "Shulker", "Silverfish",
        "Skeleton", "SkeletonHorse", "Slime", "Snowman",
        "Spider", "Squid", "Stray", "Vex", "Villager",
        "Vindicator", "Witch", "Wither", "WitherSkeleton",
        "Wolf", "Zombie", "ZombieHorse", "ZombieVillager"
    ];

    /** @var array */
    public $entityIds = [];
    /** @var array */
    public $editingId = [];

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getCommandMap()->register("slapperplus", new SlapperPlusCommand($this));
    }

    public function getFormAPI() : FormAPI {
        /** @var FormAPI $api */
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        return $api;
    }

    public function onPlayerQuit(PlayerQuitEvent $ev){
        unset($this->entityIds[$ev->getPlayer()->getName()]);
        unset($this->editingId[$ev->getPlayer()->getName()]);
    }

    public function makeSlapper(Player $player, int $type, string $name){
        $type = self::ENTITY_LIST[$type];
        $nbt = new CompoundTag();
        $nbt->Pos = new ListTag("Pos", [
            new DoubleTag("", $player->getX()),
            new DoubleTag("", $player->getY()),
            new DoubleTag("", $player->getZ())
        ]);
        $nbt->Motion = new ListTag("Motion", [
            new DoubleTag("", 0),
            new DoubleTag("", 0),
            new DoubleTag("", 0)
        ]);
        $nbt->Rotation = new ListTag("Rotation", [
            new FloatTag("", $player->getYaw()),
            new FloatTag("", $player->getPitch())
        ]);
        $nbt->Health = new ShortTag("Health", 1);
        $nbt->Commands = new CompoundTag("Commands", []);
        $nbt->MenuName = new StringTag("MenuName", "");
        $nbt->SlapperVersion = new StringTag("SlapperVersion", $this->getServer()->getPluginManager()->getPlugin("Slapper")->getDescription()->getVersion());
        if($type === "Human") {
            $player->saveNBT();
            $nbt->Inventory = clone $player->namedtag->Inventory;
            $nbt->Skin = new CompoundTag("Skin", ["Data" => new StringTag("Data", $player->getSkin()->getSkinData()), "Name" => new StringTag("Name", $player->getSkin()->getSkinId())]);
        }
        $entity = Entity::createEntity("Slapper{$type}", $player->getLevel(), $nbt);
        $entity->setNameTag($name);
        $entity->setNameTagVisible(true);
        $entity->setNameTagAlwaysVisible(true);
        $this->getServer()->getPluginManager()->callEvent(new SlapperCreationEvent($entity, "Slapper{$type}", $player, SlapperCreationEvent::CAUSE_COMMAND));
        $entity->spawnToAll();
        $player->sendMessage("§a[§bSlapperPlus§a]§6 Created {$type} entity");
    }

}
