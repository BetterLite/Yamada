<?php

namespace betterlite\yamada\game;

use betterlite\yamada\player\yPlayer;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;

interface yGameMode {

    public function getName(): string;

    public function onJoin(yPlayer $player): void;

    public function onLeave(yPlayer $player): void;

    public function onStart(): void;

    public function onTick(): void;

    public function onEnd(): void;

    public function onDamage(EntityDamageEvent $event, yPlayer $victim, ?yPlayer $attacker): void;

    public function onDeath(PlayerDeathEvent $event, yPlayer $player): void;

    public function onQuit(PlayerQuitEvent $event, yPlayer $player): void;

    public function canDamage(yPlayer $victim, yPlayer $attacker): bool;

    public function canStart(): bool;

    public function getMinPlayers(): int;

    public function getMaxPlayers(): int;

    public function getCountdownSeconds(): int;
}