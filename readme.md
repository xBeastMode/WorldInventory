# <div style="text-align: center;">WorldInventory 1.0.2</div>
### <div style="text-align: center;">by xBeastMode</div>

<div style="text-align: center;">
<img src="https://github.com/xBeastMode/WorldInventory/raw/master/icon.png" alt="">
</div>

[![](https://poggit.pmmp.io/shield.api/WorldInventory)](https://poggit.pmmp.io/p/WorldInventory)
[![](https://poggit.pmmp.io/shield.dl/WorldInventory)](https://poggit.pmmp.io/p/WorldInventory)
# Feature
- [x] Clear inventories between worlds
- [x] Link inventories between worlds
- [x] Save individual inventories per world
- [x] Define custom inventories per world
- [x] MySQL and SQLite support
- [x] Full API to extend plugin
- [ ] Command support

If you have an idea for a change or a feature create an issue!

# Configuration

The supported database types are "mysql" and "sqlite", you can find these values in the default plugin config as so:
```yaml
database:
  # The database type. "sqlite" and "mysql" are supported.
  type: sqlite
  # Edit these settings only if you choose "sqlite".
  sqlite:
    # The file name of the database in the plugin data folder.
    # You can also put an absolute path here.
    file: data.sqlite
  # Edit these settings only if you choose "mysql".
  mysql:
    host: 127.0.0.1
    # Avoid using the "root" user for security reasons.
    username: root
    password: ""
    schema: your_schema
  # The maximum number of simultaneous SQL queries
  # Recommended: 1 for sqlite, 2 for MySQL. You may want to further increase this value if your MySQL connection is very slow.
  worker-limit: 1
```

This is self-explanatory:
```yaml
# the inventory types for worlds
# clear - player's inventory gets cleared in world
# linked - player's inventory will be linked (the same) in worlds using this type
# saved - player's inventory will be unique to that world
# custom - player's inventory will be set to custom items see "items" config for example
worlds:
  world: "linked"
  foo: "linked"
  bar: "saved"
  baz: "clear"
  qux: "custom"
# custom item definitions for custom inventory type worlds
# the format should be defined as so:
# items:
#   world_name:
#     armor:
#       - "item name:item count:custom item name:custom item lore:enchantments..."
#     inventory:
#       - "item name:item count:custom item name:custom item lore:enchantments..."
#
# enchantments can be one or multiple as so:
#   protection:1:unbreaking:1...etc...
items:
  qux:
    armor:
      - "diamond_helmet:1:custom armor:custom lore:protection:1"
      - "diamond_chestplate:1:custom armor:custom lore:protection:1"
      - "diamond_leggings:1:custom armor:custom lore:protection:1"
      - "diamond_boots:1:custom armor:custom lore:protection:1"
    inventory:
      - "diamond_sword:1:custom sword:custom lore:protection:1"
```

# Inventory types
- `clear` if I go to world with this inventory type my inventory will get cleared, even if I collected items they will not be saved and everytime I go back to that world my inventory will be cleared.
- `linked` worlds that share this inventory type will combine player's inventory, meaning if I go from world `a` to `b` and they share the `linked` inventory type they will be the same exact inventory.
- `saved` instead of getting linked between worlds, player's inventory is now unique to that world. If `c` uses `saved` inventory type, and player from `a` to `c` instead of getting their inventory linked like from `a` to `b`, player's inventory is now a new inventory and everything the player has in that world will be saved only to world `c`.
- `custom` allows operator to customize the contents given to player in world, see configuration for an example.