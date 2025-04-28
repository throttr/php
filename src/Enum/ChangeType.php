<?php declare(strict_types=1);

namespace Throttr\SDK\Enum;

enum ChangeType: int
{
    case PATCH = 0x00;
    case INCREASE = 0x01;
    case DECREASE = 0x02;
}