<?php

namespace betterlite\yamada\player;

use pocketmine\player\Player;
use pocketmine\world\Position;

class yPlayer{

    private Player $player;
    private ?Position $savedLocation = null;
    private array $savedInventory = [];
    private array $savedArmor = [];
    private bool $alive = true;
    private int $kills = 0;
    private int $deaths = 0;
    private ?string $kit = null;
    private float $damageDealt = 0.0;

    public function __construct(Player $player) {
        $this->player = $player;
    }

    public function getPlayer(): Player {
        return $this->player;
    }

    public function saveState(): void {
        $this->savedLocation = $this->player->getPosition();

        $inventory = [];
        foreach ($this->player->getInventory()->getContents() as $slot => $item) {
            $inventory[$slot] = $item;
        }
        $this->savedInventory = $inventory;

        $armor = [];
        foreach ($this->player->getArmorInventory()->getContents() as $slot => $item) {
            $armor[$slot] = $item;
        }
        $this->savedArmor = $armor;
    }

    public function restoreState(): void {
        if ($this->savedLocation !== null) {
            $this->player->teleport($this->savedLocation);
        }

        $this->player->getInventory()->clearAll();
        foreach ($this->savedInventory as $slot => $item) {
            $this->player->getInventory()->setItem($slot, $item);
        }

        $this->player->getArmorInventory()->clearAll();
        foreach ($this->savedArmor as $slot => $item) {
            $this->player->getArmorInventory()->setItem($slot, $item);
        }

        $this->alive = true;
        $this->kills = 0;
        $this->deaths = 0;
        $this->kit = null;
        $this->damageDealt = 0.0;
    }

    public function isAlive(): bool { return $this->alive; }
    public function setAlive(bool $alive): void { $this->alive = $alive; }

    public function addKill(): void { $this->kills++; }
    public function addDeath(): void { $this->deaths++; }
    public function getKills(): int { return $this->kills; }
    public function getDeaths(): int { return $this->deaths; }

    public function setKit(?string $kit): void { $this->kit = $kit; }
    public function getKit(): ?string { return $this->kit; }

    public function addDamageDealt(float $damage): void { $this->damageDealt += $damage; }
    public function getDamageDealt(): float { return $this->damageDealt; }

    public function reset(): void {
        $this->alive = true;
        $this->kills = 0;
        $this->deaths = 0;
        $this->kit = null;
        $this->damageDealt = 0.0;
    }
}