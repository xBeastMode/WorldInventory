-- #! mysql
-- #{ inventories
-- #    { create
CREATE TABLE IF NOT EXISTS inventories (name TEXT NOT NULL DEFAULT "", xuid TEXT NOT NULL DEFAULT "", world TEXT, inventory TEXT, armor TEXT);
-- #    }
-- #    { insert
-- # 	  :name string
-- # 	  :xuid string
-- # 	  :world string
-- # 	  :inventory string
-- # 	  :armor string
INSERT INTO inventories (name, xuid, world, inventory, armor) VALUES (:name, :xuid, :world, :inventory, :armor);
-- #    }
-- #    { update
-- # 	  :name string
-- # 	  :xuid string
-- # 	  :world string
-- # 	  :inventory string
-- # 	  :armor string
UPDATE inventories SET inventory = :inventory, armor = :armor WHERE (name = :name OR xuid = :xuid) AND world = :world;
-- #    }
-- #    { select.xuid
-- # 	  :xuid string
-- # 	  :world string
SELECT armor, inventory FROM inventories WHERE xuid = :xuid AND world = :world;
-- #    }
-- #    { select.name
-- # 	  :name string
-- # 	  :world string
SELECT armor, inventory FROM inventories WHERE name = :name AND world = :world;
-- #    }
-- #    { select.both
-- # 	  :name string
-- # 	  :xuid string
-- # 	  :world string
SELECT armor, inventory FROM inventories WHERE (name = :name or xuid = :xuid) AND world = :world;
-- #    }
-- #}