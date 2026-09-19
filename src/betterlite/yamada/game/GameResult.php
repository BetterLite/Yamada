<?php

namespace betterlite\yamada\game;

enum GameResult: string{

    case WIN = "win";
    case LOSE = "lose";
    case DRAW = "draw";
    case SPECTATING = "spectating";

}