<?php

declare(strict_types = 1);

namespace jojoe77777\SlapperPlus;

use jojoe77777\SlapperPlus\commands\SlapperPlusCommand;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\nbt\tag\CompoundTag;
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

    public function onPlayerQuit(PlayerQuitEvent $ev){
        unset($this->entityIds[$ev->getPlayer()->getName()]);
        unset($this->editingId[$ev->getPlayer()->getName()]);
    }

    private function makeNBT($type, Player $player, string $name): CompoundTag {
        $nbt = Entity::createBaseNBT($player, null, $player->getYaw(), $player->getPitch());
        $nbt->setShort("Health", 1);
        $nbt->setTag(new CompoundTag("Commands", []));
        $nbt->setString("MenuName", "");
        $nbt->setString("CustomName", $name);
        $nbt->setString("SlapperVersion", $this->getDescription()->getVersion());
        if ($type === "Human") {
            $player->saveNBT();
            
            $inventoryTag = $player->namedtag->getListTag("Inventory");
            assert($inventoryTag !== null);
            $nbt->setTag(clone $inventoryTag);
            
            $skinTag = $player->namedtag->getCompoundTag("Skin");
            assert($skinTag !== null);
            $nbt->setTag(clone $skinTag);
        }
        return $nbt;
    }

    public function makeSlapper(Player $player, int $type, string $name){
        $type = self::ENTITY_LIST[$type];
        $nbt = $this->makeNBT($type, $player, $name);
        $entity = Entity::createEntity("Slapper" . $type, $player->getLevel(), $nbt);
        $entity->spawnToAll();
        $event = new SlapperCreationEvent($entity, "Slapper" . $type, $player, SlapperCreationEvent::CAUSE_COMMAND);
        $event->call();
        $entity->spawnToAll();
        $player->sendMessage("§a[§bSlapperPlus§a]§6 Created {$type} entity");
    }
}
