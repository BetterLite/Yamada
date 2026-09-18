<?php

namespace betterlite\yamada\arena;

enum ArenaState: string{

    case WAITING = "waiting";
    case STARTING = "starting";
    case RUNNING = "running";
    case ENDING = "ending";
}