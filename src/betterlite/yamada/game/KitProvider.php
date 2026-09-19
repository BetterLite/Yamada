<?php

namespace betterlite\yamada\game;

use pocketmine\item\Item;
use pocketmine\player\Player;

class KitProvider {

    /** @var array<string, Item[]> */
    private array $kits = [];

    public function addKit(string $name, array $items): void {
        $this->kits[$name] = $items;
    }

    public function removeKit(string $name): void {
        unset($this->kits[$name]);
    }

    public function hasKit(string $name): bool {
        return isset($this->kits[$name]);
    }

    public function getKit(string $name): ?array {
        return $this->kits[$name] ?? null;
    }

    public function getKits(): array {
        return $this->kits;
    }

    public function getKitNames(): array {
        return array_keys($this->kits);
    }

    public function applyKit(Player $player, string $kitName): bool {
        if (!isset($this->kits[$kitName])) {
            return false;
        }

        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();

        foreach ($this->kits[$kitName] as $slot => $item) {
            if (is_int($slot)) {
                $player->getInventory()->setItem($slot, $item);
            }
        }

        return true;
    }
}