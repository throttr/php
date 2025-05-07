<?php

namespace Throttr\SDK\Requests;

use Throttr\SDK\Contracts\ShouldDefineSerialization;
use Throttr\SDK\Enum\ValueSize;

abstract class BaseRequest implements ShouldDefineSerialization {
    /**
     * Type
     *
     * @var int
     */
    public int $type = 0x00;


    /**
     * Pack
     *
     * @param ValueSize $size
     * @return string
     */
    public static function pack(ValueSize $size): string {
        return match ($size) {
            ValueSize::UINT8 => 'C',
            ValueSize::UINT16 => 'v',
            ValueSize::UINT32 => 'V',
            ValueSize::UINT64 => 'P',
        };
    }
}