<?php

declare(strict_types=1);

namespace App\Enums;

enum WriteupDifficulty: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';
    case Insane = 'insane';
}