<?php

declare(strict_types=1);

namespace xBeastMode\WorldInventory;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\player\Player;
final class PlayerWorldInventoryResult{
        public PlayerInventory $inventory;
        public ArmorInventory $armorInventory;

        public function __construct(Player $player){
                $this->inventory = new PlayerInventory($player);
                $this->armorInventory = new ArmorInventory($player);
        }
}