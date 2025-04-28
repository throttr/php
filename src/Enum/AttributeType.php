<?php declare(strict_types=1);

namespace Throttr\SDK\Enum;

enum AttributeType: int
{
    case QUOTA = 0x00;
    case TTL = 0x01;
}