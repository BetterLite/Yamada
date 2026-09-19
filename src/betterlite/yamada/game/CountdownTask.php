<?php

namespace betterlite\yamada\game;

use betterlite\yamada\arena\Arena;
use pocketmine\scheduler\Task;

class CountdownTask extends Task {

    private int $secondsLeft;

    public function __construct(private Arena $arena, private int $seconds) {
        $this->secondsLeft = $seconds;
    }

    public function onRun(): void {
        if (!$this->arena->isInCountdown()) {
            $this->getHandler()->cancel();
            return;
        }

        if ($this->secondsLeft <= 0) {
            $this->arena->start();
            $this->getHandler()->cancel();
            return;
        }

        if ($this->secondsLeft <= 5 || $this->secondsLeft % 5 === 0) {
            foreach ($this->arena->getPlayers() as $mgPlayer) {
                $mgPlayer->getPlayer()->sendTitle(
                    "§e" . $this->secondsLeft,
                    "§7La partita sta per iniziare",
                    10, 20, 10
                );
            }
        }

        $this->secondsLeft--;
    }

    public function getSecondsLeft(): int {
        return $this->secondsLeft;
    }
}