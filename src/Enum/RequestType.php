<?php declare(strict_types=1);

namespace Throttr\SDK\Enum;

enum RequestType: int
{
    case INSERT = 0x01;
    case QUERY = 0x02;
    case UPDATE = 0x03;
    case PURGE = 0x04;
}