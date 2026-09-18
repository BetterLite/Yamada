<?php

namespace betterlite\yamada\arena;

use betterlite\yamada\exception\ArenaException;
use betterlite\yamada\player\yPlayer;
use pocketmine\player\Player;
use pocketmine\world\Position;

abstract class Arena{

    protected string $name;
    protected ArenaState $state;
    protected Position $lobby;
    protected Position $spectator;
    /** @var Position[] */
    protected array $spawns = [];
    /** @var yPlayer[] */
    protected array $players = [];
    protected int $minPlayers;
    protected int $maxPlayers;

    public function __construct(string $name, Position $lobby, Position $spectator, int $minPlayers = 2, int $maxPlayers = 16) {
        if ($minPlayers < 1) {
            throw new ArenaException("Min players must be at least 1");
        }
        if ($maxPlayers < $minPlayers) {
            throw new ArenaException("Max players must be >= min players");
        }

        $this->name = $name;
        $this->lobby = $lobby;
        $this->spectator = $spectator;
        $this->minPlayers = $minPlayers;
        $this->maxPlayers = $maxPlayers;
        $this->state = ArenaState::WAITING;
    }

    abstract public function onJoin(Player $player): void;
    abstract public function onLeave(Player $player): void;
    abstract public function onStart(): void;
    abstract public function onTick(): void;
    abstract public function onEnd(): void;
    abstract public function getGameType(): string;

    public function addPlayer(Player $player): bool {
        $uuid = $player->getUniqueId()->toString();

        if ($this->state !== ArenaState::WAITING) {
            return false;
        }

        if (isset($this->players[$uuid])) {
            return false;
        }

        if (count($this->players) >= $this->maxPlayers) {
            return false;
        }

        $mgPlayer = new MinigamePlayer($player);
        $mgPlayer->saveState();
        $this->players[$uuid] = $mgPlayer;

        $this->onJoin($player);
        return true;
    }

    public function removePlayer(Player $player): void {
        $uuid = $player->getUniqueId()->toString();

        if (!isset($this->players[$uuid])) {
            return;
        }

        $this->players[$uuid]->restoreState();
        $this->onLeave($player);

        unset($this->players[$uuid]);

        if ($this->state === ArenaState::RUNNING && count($this->players) === 0) {
            $this->end();
        }
    }

    public function start(): void {
        if (!$this->canStart()) {
            throw new ArenaException("Arena cannot start");
        }

        $this->state = ArenaState::STARTING;
        $this->onStart();
        $this->state = ArenaState::RUNNING;
    }

    public function end(): void {
        if ($this->state === ArenaState::ENDING) {
            return;
        }

        $this->state = ArenaState::ENDING;
        $this->onEnd();

        foreach ($this->players as $mgPlayer) {
            $this->onLeave($mgPlayer->getPlayer());
            $mgPlayer->restoreState();
        }
        $this->players = [];
        $this->state = ArenaState::WAITING;
    }

    public function tick(): void {
        if ($this->state !== ArenaState::RUNNING) {
            return;
        }
        $this->onTick();
    }

    public function canStart(): bool {
        return count($this->players) >= $this->minPlayers
            && $this->state === ArenaState::WAITING;
    }

    public function isFull(): bool {
        return count($this->players) >= $this->maxPlayers;
    }

    public function isPlayer(Player $player): bool {
        return isset($this->players[$player->getUniqueId()->toString()]);
    }

    public function getMinigamePlayer(Player $player): ?yPlayer {
        return $this->players[$player->getUniqueId()->toString()] ?? null;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getState(): ArenaState {
        return $this->state;
    }

    public function setState(ArenaState $state): void {
        $this->state = $state;
    }

    public function getLobby(): Position {
        return $this->lobby;
    }

    public function getSpectator(): Position {
        return $this->spectator;
    }

    public function getSpawns(): array {
        return $this->spawns;
    }

    public function addSpawn(Position $pos): void {
        $this->spawns[] = $pos;
    }

    public function getPlayers(): array {
        return $this->players;
    }

    public function getPlayerCount(): int {
        return count($this->players);
    }

    public function getMinPlayers(): int {
        return $this->minPlayers;
    }

    public function getMaxPlayers(): int {
        return $this->maxPlayers;
    }
}