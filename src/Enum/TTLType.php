<?php declare(strict_types=1);

namespace Throttr\SDK\Enum;

enum TTLType: int
{
    case NANOSECONDS = 0x00;
    case MILLISECONDS = 0x01;
    case SECONDS = 0x02;
}