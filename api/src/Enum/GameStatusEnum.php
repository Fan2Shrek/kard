<?php

namespace App\Enum;

enum GameStatusEnum: string
{
    case WAITING = 'waiting';
    case PLAYING = 'playing';
    case FINISHED = 'finished';
}
