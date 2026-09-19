<?php

declare(strict_types=1);

namespace betterlite\yamada\arena;

use betterlite\yamada\exception\ArenaException;
use betterlite\yamada\game\CountdownManager;
use betterlite\yamada\game\yGameMode;
use betterlite\yamada\player\yPlayer;
use pocketmine\player\Player;
use pocketmine\world\Position;

abstract class Arena {

    protected string $name;
    protected ArenaState $state;
    protected bool $counting = false;
    protected Position $lobby;
    protected Position $spectator;
    /** @var Position[] */
    protected array $spawns = [];
    /** @var yPlayer[] */
    protected array $players = [];
    protected int $minPlayers;
    protected int $maxPlayers;
    protected int $countdownSeconds = 10;
    protected ?yGameMode $gameMode = null;
    protected ?CountdownManager $countdownManager = null;

    public function __construct(string $name, Position $lobby, Position $spectator, int $minPlayers = 2, int $maxPlayers = 16, int $countdownSeconds = 10) {
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
        $this->countdownSeconds = $countdownSeconds;
        $this->state = ArenaState::WAITING;
    }

    abstract public function onJoin(Player $player): void;
    abstract public function onLeave(Player $player): void;
    abstract public function onStart(): void;
    abstract public function onTick(): void;
    abstract public function onEnd(): void;
    abstract public function getGameType(): string;

    public function setGameMode(?yGameMode $mode): void {
        $this->gameMode = $mode;
    }

    public function getGameMode(): ?yGameMode {
        return $this->gameMode;
    }

    public function setCountdownManager(CountdownManager $manager): void {
        $this->countdownManager = $manager;
    }

    public function isInCountdown(): bool {
        return $this->counting;
    }

    public function setCounting(bool $counting): void {
        $this->counting = $counting;
    }

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

        $mgPlayer = new yPlayer($player);
        $mgPlayer->saveState();
        $this->players[$uuid] = $mgPlayer;

        $this->onJoin($player);
        $this->checkCountdown();
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

        if ($this->counting && count($this->players) < $this->minPlayers) {
            if ($this->countdownManager !== null) {
                $this->countdownManager->stopCountdown($this);
            }
        }

        if ($this->state === ArenaState::RUNNING) {
            $this->checkWinCondition();

            if (count($this->players) === 0) {
                $this->end();
            }
        }
    }

    public function checkCountdown(): void {
        if ($this->state !== ArenaState::WAITING) {
            return;
        }

        if (count($this->players) >= $this->minPlayers && !$this->counting) {
            if ($this->countdownManager !== null) {
                $this->countdownManager->startCountdown($this, $this->countdownSeconds);
            }
        }
    }

    public function checkWinCondition(): void {
        if ($this->state !== ArenaState::RUNNING) {
            return;
        }

        $alive = 0;
        foreach ($this->players as $mgPlayer) {
            if ($mgPlayer->isAlive()) {
                $alive++;
            }
        }

        if ($alive <= 1) {
            $this->end();
        }
    }

    public function start(): void {
        if (!$this->canStart()) {
            throw new ArenaException("Arena cannot start");
        }

        if ($this->countdownManager !== null && $this->countdownManager->isCounting($this)) {
            $this->countdownManager->stopCountdown($this);
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

    public function getyPlayer(Player $player): ?yPlayer {
        return $this->players[$player->getUniqueId()->toString()] ?? null;
    }

    public function getName(): string { return $this->name; }
    public function getState(): ArenaState { return $this->state; }
    public function setState(ArenaState $state): void { $this->state = $state; }
    public function getLobby(): Position { return $this->lobby; }
    public function getSpectator(): Position { return $this->spectator; }
    public function getSpawns(): array { return $this->spawns; }
    public function addSpawn(Position $pos): void { $this->spawns[] = $pos; }
    public function getPlayers(): array { return $this->players; }
    public function getPlayerCount(): int { return count($this->players); }
    public function getMinPlayers(): int { return $this->minPlayers; }
    public function getMaxPlayers(): int { return $this->maxPlayers; }
    public function getCountdownSeconds(): int { return $this->countdownSeconds; }
}