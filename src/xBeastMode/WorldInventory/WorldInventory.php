<?php

declare(strict_types=1);

namespace xBeastMode\WorldInventory;
use Closure;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
class WorldInventory extends PluginBase{
        use SingletonTrait;

        /** @var DataConnector */
        private DataConnector $database;

        protected function onEnable(): void{
                $this->saveDefaultConfig();
                $this->database = libasynql::create($this, $this->getConfig()->get("database"), [
                        "sqlite" => "sqlite.sql",
                        "mysql"  => "mysql.sql"
                ]);

                $this->database->executeGeneric("inventories.create");
                $this->getServer()->getPluginManager()->registerEvents(new WorldInventoryListener($this), $this);
        }

        protected function onDisable(): void{
                if(isset($this->database)) $this->database->close();
        }

        /**
         * Returns the inventory type used for passed world name
         *
         * @param string $world
         *
         * @return string
         */
        public function getWorldInventoryType(string $world): string{
                /** @var string $output */
                $output = $this->getConfig()->getNested("worlds.$world", "@linked");
                return $output;
        }

        /**
         * Returns the custom inventory items defined by operator in config
         *
         * @param string $world
         *
         * @return array<int, array<int, Item|null>>
         */
        public function getWorldCustomItems(string $world): array{
                /** @var string[][] $items */
                $items = $this->getConfig()->getNested("items.$world", [[], []]);

                if(count($items) === 0){
                        return $items;
                }

                return [ItemParser::parse($items["inventory"]), ItemParser::parse($items["armor"])];
        }

        /**
         * @api Save player's world inventory
         *
         * Use this function when you want to save player's world inventory
         * directly from player object without the hassle of passing raw contents.
         * To use this function it is recommended that player whose object is passed is online
         *
         * @param string       $world    the name of the world where player's inventory contents will be saved
         * @param Player       $player   the player object of player whose inventory contents will be saved
         * @param Closure|null $onResult this function will be called when the sql save query finishes -
         *                               the function parameters should be: function (int $insertId, int $affectedRows)
         */
        public function savePlayerWorldInventory(string $world, Player $player, ?Closure $onResult = null): void{
                $this->savePlayerWorldInventoryRaw($world, $player->getName(), $player->getXuid(), $player->getInventory()->getContents(), $player->getArmorInventory()->getContents(), $onResult);
        }

        /**
         * @api save player's world inventory raw
         *
         * Use this function when you want to save player's world inventory passing the raw
         * contents, including player's inventory contents and player's armor contents.
         * It is recommended that player is online at the moment of using this function
         * because it requires the xuid of player, you can optionally leave this blank ("")
         * but it may conflict later on if player changes name: it won't be able to fetch old -
         * name contents with new name.
         *
         * @param string       $world             the name of the world where player's inventory contents will be saved
         * @param string       $name              the name of player whose inventory contents will be saved
         * @param string       $xuid              the xuid of player whose inventory contents will be saved
         * @param Item[]       $inventoryContents player's raw inventory contents
         * @param Item[]       $armorContents     player's raw armor contents
         * @param Closure|null $onResult          this function will be called when the sql save query finishes -
         *                                        the function parameters should be: function (int $insertId, int $affectedRows)
         */
        public function savePlayerWorldInventoryRaw(string $world, string $name, string $xuid, array $inventoryContents, array $armorContents, ?Closure $onResult = null): void{
                $args = [
                        "xuid"      => $xuid,
                        "name"      => $name,
                        "world"     => $world,
                        "inventory" => $this->encodeInventoryContents($inventoryContents),
                        "armor"     => $this->encodeInventoryContents($armorContents),
                ];
                $this->playerWorldInventoryContentsExist($world, $name, $xuid, function(bool $exists) use ($args, $onResult){
                        $this->database->executeGeneric($exists ? "inventories.update" : "inventories.insert", $args, $onResult);
                });
        }

        /**
         * @api save player's world inventory raw using player's name
         *
         * Use this function when you want to save player's world inventory passing the raw
         * contents, including player's inventory contents and player's armor contents.
         *
         * WARNING: It is NOT RECOMMENDED to use this function because it is saved with name rather than xuid
         * which it may conflict later on if player changes name: it won't be able to fetch old -
         * name contents with new name.
         *
         * @param string       $world             the name of the world where player's inventory contents will be saved
         * @param string       $name              the name of player whose inventory contents will be saved
         * @param Item[]       $inventoryContents player's raw inventory contents
         * @param Item[]       $armorContents     player's raw armor contents
         * @param Closure|null $onResult          this function will be called when the sql save query finishes -
         *                                        the function parameters should be: function (int $insertId, int $affectedRows)
         */
        public function savePlayerNameWorldInventoryRaw(string $world, string $name, array $inventoryContents, array $armorContents, ?Closure $onResult = null): void{
                $this->savePlayerWorldInventoryRaw($world, $name, $name, $inventoryContents, $armorContents, $onResult);
        }

        /**
         * @api save player's world inventory raw using player's xuid
         *
         * Use this function when you want to save player's world inventory passing the raw
         * contents, including player's inventory contents and player's armor contents.
         *
         * NOTE: This function saves player's contents using xuid rather than name, so in future if you want
         * to fetch with name you will not be able to unless getting xuid from online player.
         *
         * @param string       $world             the name of the world where player's inventory contents will be saved
         * @param string       $xuid              the xuid of player whose inventory contents will be saved
         * @param Item[]       $inventoryContents player's raw inventory contents
         * @param Item[]       $armorContents     player's raw armor contents
         * @param Closure|null $onResult         this function will be called when the sql save query finishes -
         *                                        the function parameters should be: function (int $insertId, int $affectedRows)
         */
        public function savePlayerXuidWorldInventoryRaw(string $world, string $xuid, array $inventoryContents, array $armorContents, ?Closure $onResult = null): void{
                $this->savePlayerWorldInventoryRaw($world, $xuid, $xuid, $inventoryContents, $armorContents, $onResult);
        }

        /**
         * @api fetch player's world inventory
         *
         * Use this function when you want to fetch player's world inventory as inventories,
         * including player's inventory contents and player's armor contents.
         *
         * @param string  $world    the name of the world from where player's inventory contents will be fetched
         * @param Player  $player   the player object of player whose inventory contents will be fetched
         * @param Closure $onResult this function will be called when the function throws result -
         *                          the function parameters should be: function (@link PlayerWorldInventoryResult $result)
         */
        public function getPlayerWorldInventory(string $world, Player $player, Closure $onResult): void{
                $this->getPlayerWorldInventoryRaw($world, $player->getName(), $player->getXuid(), function(RawPlayerWorldInventoryResult $result) use ($onResult, $player){
                        $newResult = new PlayerWorldInventoryResult($player);

                        $newResult->inventory->setContents($result->inventoryContents);
                        $newResult->armorInventory->setContents($result->armorContents);

                        $onResult($newResult);
                });
        }

        /**
         * @api fetch player's world inventory raw
         *
         * Use this function when you want to fetch player's world inventory as raw item array,
         * including player's inventory contents and player's armor contents.
         *
         * @param string $world     the name of the world from where player's inventory contents will be fetched
         * @param string $name      the name of player whose inventory contents will be fetched
         * @param string $xuid      the xuid of player whose inventory contents will be fetched
         * @param Closure $onResult this function will be called when the function throws result -
         *                          the function parameters should be: function (@link RawPlayerWorldInventoryResult $result)
         */
        public function getPlayerWorldInventoryRaw(string $world, string $name, string $xuid, Closure $onResult): void{
                $this->database->executeSelect("inventories.select.both", [
                        "xuid"  => $xuid,
                        "name"  => $name,
                        "world" => $world
                ], function(array $rows) use ($onResult){
                        if(!empty($rows)){
                                $result = new RawPlayerWorldInventoryResult($this->decodeInventoryData($rows[0]["inventory"]), $this->decodeInventoryData($rows[0]["armor"]));
                                $onResult($result);
                        }else{
                                $onResult(new RawPlayerWorldInventoryResult());
                        }
                }, fn(SqlError $error) => $this->getLogger()->error($error->getErrorMessage()));
        }

        /**
         * @api fetch player's world inventory raw using player's name
         *
         * Use this function when you want to fetch player's world inventory as raw item array,
         * including player's inventory contents and player's armor contents.
         *
         * @param string $world     the name of the world from where player's inventory contents will be fetched
         * @param string $name      the name of player whose inventory contents will be fetched
         * @param Closure $onResult this function will be called when the function throws result -
         *                          the function parameters should be: function (@link RawPlayerWorldInventoryResult $result)
         */
        public function getPlayerWorldInventoryByName(string $world, string $name, Closure $onResult): void{
                $this->getPlayerWorldInventoryRaw($world, $name, $name, $onResult);
        }

        /**
         * @api fetch player's world inventory raw using player's xuid
         *
         * Use this function when you want to fetch player's world inventory as raw item array,
         * including player's inventory contents and player's armor contents.
         *
         * @param string $world     the name of the world from where player's inventory contents will be fetched
         * @param string $xuid      the xuid of player whose inventory contents will be fetched
         * @param Closure $onResult this function will be called when the function throws result -
         *                          the function parameters should be: function (@link RawPlayerWorldInventoryResult $result)
         */
        public function getPlayerWorldInventoryByXuid(string $world, string $xuid, Closure $onResult): void{
                $this->getPlayerWorldInventoryRaw($world, $xuid, $xuid, $onResult);
        }

        /**
         * @internal
         *
         * @param string $world
         * @param string $name
         * @param string $xuid
         * @param Closure $onResult
         */
        private function playerWorldInventoryContentsExist(string $world, string $name, string $xuid, Closure $onResult): void{
                $this->database->executeSelect("inventories.select.both", [
                        "name"  => $name,
                        "xuid" => $xuid,
                        "world" => $world
                ], fn(array $rows) => $onResult(!empty($rows)), fn(SqlError $error) => $this->getLogger()->error($error->getErrorMessage()));
        }

        /**
         * @internal
         *
         * @param Item[] $contents
         *
         * @return string
         */
        private function encodeInventoryContents(array $contents): string{
                array_walk($contents, fn(Item &$item, int $key) => $item = $item->jsonSerialize());
                $_ = json_encode($contents);
                return $_ === false ? "" : $_;
        }

        /**
         * @internal
         *
         * @param string $data
         *
         * @return Item[]
         */
        private function decodeInventoryData(string $data): array{
                /**
                 * @var array<string, int|string> $_
                 * @phpstan-var array{
                 * 	id: int,
                 * 	damage?: int,
                 * 	count?: int,
                 * 	nbt?: string,
                 * 	nbt_hex?: string,
                 * 	nbt_b64?: string
                 * } $_
                 */
                $_ = json_decode($data, true);
                array_walk($_, fn(array &$item_data) => $item_data = Item::jsonDeserialize($item_data));
                return $_;
        }
}