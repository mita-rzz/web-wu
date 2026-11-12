<?php

declare(strict_types=1);

namespace App\Enums;

enum MediaFileExt: string
{
    case Jpg = 'jpg';
    case Jpeg = 'jpeg';
    case Png = 'png';
    case Gif = 'gif';
    case Webp = 'webp';
}