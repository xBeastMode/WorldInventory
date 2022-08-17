<?php

declare(strict_types=1);

namespace xBeastMode\WorldInventory;
use Closure;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
class WorldInventoryListener implements Listener{
        /**
         * @param WorldInventory $plugin
         */
        public function __construct(private WorldInventory $plugin){}

        /**
         * @param PlayerJoinEvent $event
         *
         * @priority HIGHEST
         */
        public function onPlayerJoin(PlayerJoinEvent $event): void{
                $player = $event->getPlayer();
                $world = $player->getWorld()->getFolderName();

                $this->fetch($world, $player);
        }

        /**
         * @param PlayerQuitEvent $event
         *
         * @priority HIGHEST
         */
        public function onPlayerQuit(PlayerQuitEvent $event): void{
                $player = $event->getPlayer();
                $world = $player->getWorld()->getFolderName();

                $this->save($world, $player);
        }

        /**
         * @param EntityTeleportEvent $event
         *
         * @priority HIGHEST
         *
         * @handleCancelled
         */
        public function onEntityTeleport(EntityTeleportEvent $event): void{
                $player = $event->getEntity();
                $from = $event->getFrom();
                $to = $event->getTo();

                if($player instanceof Player && $from->getWorld() !== $to->getWorld()){
                        $fromWorld = $from->getWorld()->getFolderName();
                        $toWorld = $to->getWorld()->getFolderName();

                        $this->save($fromWorld, $player, fn() => $this->fetch($toWorld, $player));
                }
        }

        /**
         * Saves player's world inventory contents based on world inventory type
         *
         * @param string       $world
         * @param Player       $player
         * @param Closure|null $onFinished
         */
        private function save(string $world, Player $player, ?Closure $onFinished = null): void{
                switch($this->plugin->getWorldInventoryType($world)){
                        case "linked":
                                $this->plugin->savePlayerWorldInventory("linked", $player, $onFinished);
                                break;
                        case "saved":
                                $this->plugin->savePlayerWorldInventory($world, $player, $onFinished);
                                break;
                        default:
                                $onFinished();
                                break;
                }
        }

        /**
         * Fetches player's world inventory contents based on world inventory type
         *
         * @param string $world
         * @param Player $player
         */
        private function fetch(string $world, Player $player): void{
                switch($this->plugin->getWorldInventoryType($world)){
                        case "linked":
                                $this->plugin->getPlayerWorldInventory("linked", $player, function(PlayerWorldInventoryResult $result) use ($player){
                                        $player->getInventory()->setContents($result->inventory->getContents());
                                        $player->getArmorInventory()->setContents($result->armorInventory->getContents());
                                });
                                break;
                        case "saved":
                                $this->plugin->getPlayerWorldInventory($world, $player, function(PlayerWorldInventoryResult $result) use ($player){
                                        $player->getInventory()->setContents($result->inventory->getContents());
                                        $player->getArmorInventory()->setContents($result->armorInventory->getContents());
                                });
                                break;
                        case "clear":
                                $player->getInventory()->clearAll();
                                $player->getArmorInventory()->clearAll();
                                break;
                        case "custom":
                                $items = $this->plugin->getWorldCustomItems($world);

                                $player->getInventory()->setContents($items[0]);
                                $player->getArmorInventory()->setContents($items[1]);
                                break;
                }
        }
}