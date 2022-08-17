<?php

declare(strict_types=1);

namespace xBeastMode\WorldInventory;
class RawPlayerWorldInventoryResult{
        public function __construct(
                public array $inventoryContents = [],
                public array $armorContents = []
        ){}
}