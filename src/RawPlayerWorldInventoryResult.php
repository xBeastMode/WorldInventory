<?php

namespace xBeastMode\WorldInventory;

class RawPlayerWorldInventoryResult{
        public function __construct(
                public array $inventoryContents = [],
                public array $armorContents = []
        ){}
}