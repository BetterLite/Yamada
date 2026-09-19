<?php

namespace betterlite\yamada\arena;

use pocketmine\world\Position;

class ArenaData {

    public function __construct(
        public string $name,
        public Position $lobby,
        public Position $spectator,
        public int $minPlayers = 2,
        public int $maxPlayers = 16,
        public int $countdownSeconds = 10,
        /** @var Position[] */
        public array $spawns = []
    ) {}
}