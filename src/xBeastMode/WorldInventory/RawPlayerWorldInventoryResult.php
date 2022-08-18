<?php

declare(strict_types=1);

namespace xBeastMode\WorldInventory;
use pocketmine\item\Item;
class RawPlayerWorldInventoryResult{
        /**
         * @param Item[] $inventoryContents
         * @param Item[] $armorContents
         */
        public function __construct(
                public array $inventoryContents = [],
                public array $armorContents = []
        ){}
}