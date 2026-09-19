<?php

namespace betterlite\yamada\game;

use betterlite\yamada\arena\Arena;
use pocketmine\scheduler\TaskScheduler;

class CountdownManager {

    /** @var CountdownTask[] */
    private array $countdowns = [];

    public function __construct(private TaskScheduler $scheduler) {}

    public function startCountdown(Arena $arena, int $seconds): void {
        if (isset($this->countdowns[$arena->getName()])) {
            $this->stopCountdown($arena);
        }

        $arena->setCounting(true);
        $task = new CountdownTask($arena, $seconds);
        $this->countdowns[$arena->getName()] = $task;
        $this->scheduler->scheduleRepeatingTask($task, 20);
    }

    public function stopCountdown(Arena $arena): void {
        $name = $arena->getName();
        if (isset($this->countdowns[$name])) {
            $this->countdowns[$name]->getHandler()->cancel();
            unset($this->countdowns[$name]);
            $arena->setCounting(false);
        }
    }

    public function isCounting(Arena $arena): bool {
        return isset($this->countdowns[$arena->getName()]);
    }

    public function getCountdown(Arena $arena): ?CountdownTask {
        return $this->countdowns[$arena->getName()] ?? null;
    }

    public function stopAll(): void {
        foreach ($this->countdowns as $task) {
            $task->getHandler()->cancel();
        }
        $this->countdowns = [];
    }
}