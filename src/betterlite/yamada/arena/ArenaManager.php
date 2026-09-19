<?php

declare(strict_types=1);

namespace betterlite\yamada\arena;

use betterlite\yamada\exception\ArenaException;
use betterlite\yamada\game\CountdownManager;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\scheduler\TaskScheduler;

class ArenaManager {

    /** @var Arena[] */
    private array $arenas = [];
    private TaskScheduler $scheduler;
    private CountdownManager $countdownManager;
    private ?TaskHandler $tickTask = null;
    private int $tickRate = 20;

    public function __construct(TaskScheduler $scheduler) {
        $this->scheduler = $scheduler;
        $this->countdownManager = new CountdownManager($scheduler);
    }

    public function registerArena(Arena $arena): void {
        if (isset($this->arenas[$arena->getName()])) {
            throw new ArenaException("Arena {$arena->getName()} already registered");
        }
        $arena->setCountdownManager($this->countdownManager);
        $this->arenas[$arena->getName()] = $arena;
    }

    public function unregisterArena(string $name): void {
        if (!isset($this->arenas[$name])) {
            throw new ArenaException("Arena {$name} not found");
        }

        $arena = $this->arenas[$name];
        $this->countdownManager->stopCountdown($arena);
        if ($arena->getState() === ArenaState::RUNNING) {
            $arena->end();
        }

        unset($this->arenas[$name]);
    }

    public function getArena(string $name): ?Arena {
        return $this->arenas[$name] ?? null;
    }

    public function getArenas(): array {
        return $this->arenas;
    }

    public function getAvailableArena(int $players = 1): ?Arena {
        foreach ($this->arenas as $arena) {
            if ($arena->getState() === ArenaState::WAITING
                && !$arena->isFull()
                && $arena->getPlayerCount() + $players <= $arena->getMaxPlayers()) {
                return $arena;
            }
        }
        return null;
    }

    public function joinRandomArena(Player $player): bool {
        $arena = $this->getAvailableArena();
        if ($arena === null) {
            return false;
        }
        return $arena->addPlayer($player);
    }

    public function getPlayerArena(Player $player): ?Arena {
        foreach ($this->arenas as $arena) {
            if ($arena->isPlayer($player)) {
                return $arena;
            }
        }
        return null;
    }

    public function removePlayerFromAll(Player $player): void {
        foreach ($this->arenas as $arena) {
            if ($arena->isPlayer($player)) {
                $arena->removePlayer($player);
            }
        }
    }

    public function getCountdownManager(): CountdownManager {
        return $this->countdownManager;
    }

    public function startTickTask(int $tickRate = 20): void {
        if ($this->tickTask !== null) {
            return;
        }

        $this->tickRate = $tickRate;

        $this->tickTask = $this->scheduler->scheduleRepeatingTask(
            new ClosureTask(
                function(): void {
                    foreach ($this->arenas as $arena) {
                        if ($arena->getState() === ArenaState::RUNNING) {
                            $arena->tick();
                        }
                    }
                }
            ),
            $tickRate
        );
    }

    public function stopTickTask(): void {
        if ($this->tickTask !== null) {
            $this->tickTask->cancel();
            $this->tickTask = null;
        }
    }

    public function shutdown(): void {
        $this->countdownManager->stopAll();
        $this->stopTickTask();
        foreach ($this->arenas as $arena) {
            if ($arena->getState() === ArenaState::RUNNING) {
                $arena->end();
            }
        }
        $this->arenas = [];
    }
}