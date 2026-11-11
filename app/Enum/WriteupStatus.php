<?php

declare(strict_types=1);

namespace App\Enums;

enum WriteupStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}