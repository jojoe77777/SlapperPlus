<?php

declare(strict_types = 1);

namespace jojoe77777\SlapperPlus;

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

    /** @var FormAPI */
    public $formAPI;

    public function onEnable() {
        $this->formAPI = new FormAPI();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getCommandMap()->register("slapperplus", new SlapperPlusCommand($this));
    }

    public function getFormAPI() : FormAPI {
        return $this->formAPI;
    }

    public function onPlayerQuit(PlayerQuitEvent $ev){
        unset($this->entityIds[$ev->getPlayer()->getName()]);
        unset($this->editingId[$ev->getPlayer()->getName()]);
    }

    public function makeSlapper(Player $player, int $type, string $name){
        $type = self::ENTITY_LIST[$type];
        $nbt = new CompoundTag();
        $pos = new ListTag("Pos", [
            new DoubleTag("", $player->getX()),
            new DoubleTag("", $player->getY()),
            new DoubleTag("", $player->getZ())
        ]);
        $motion = new ListTag("Motion", [
            new DoubleTag("", 0),
            new DoubleTag("", 0),
            new DoubleTag("", 0)
        ]);
        $rotation = new ListTag("Rotation", [
            new FloatTag("", $player->getYaw()),
            new FloatTag("", $player->getPitch())
        ]);
        $health = new ShortTag("Health", 1);
        $commands = new CompoundTag("Commands", []);
        $menuName = new StringTag("MenuName", "");
        $slapperVersion = new StringTag("SlapperVersion", $this->getServer()->getPluginManager()->getPlugin("Slapper")->getDescription()->getVersion());

        $nbt->setTag($pos);
        $nbt->setTag($motion);
        $nbt->setTag($rotation);
        $nbt->setTag($health);
        $nbt->setTag($commands);
        $nbt->setTag($menuName);
        $nbt->setTag($slapperVersion);

        if($type === "Human") {
            $player->saveNBT();
            $inventory = clone $player->namedtag->getListTag("Inventory");
            $skin = new CompoundTag("Skin", ["Data" => new StringTag("Data", $player->getSkin()->getSkinData()), "Name" => new StringTag("Name", $player->getSkin()->getSkinId())]);
            $nbt->setTag($inventory);
            $nbt->setTag($skin);
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
