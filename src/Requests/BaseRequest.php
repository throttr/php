<?php

namespace Throttr\SDK\Requests;

use Throttr\SDK\Contracts\ShouldDefineSerialization;
use Throttr\SDK\Enum\RequestType;
use Throttr\SDK\Enum\ValueSize;

/**
 * Base request
 */
abstract class BaseRequest implements ShouldDefineSerialization {
    /**
     * Type
     *
     * @var RequestType
     */
    public RequestType $type = RequestType::INSERT;

    /**
     * Pack
     *
     * @param ValueSize $size
     * @return string
     */
    public static function pack(ValueSize $size): string {
        return match ($size) {
            ValueSize::UINT8 => 'C', // @codeCoverageIgnore
            ValueSize::UINT16 => 'v', // @codeCoverageIgnore
            ValueSize::UINT32 => 'V', // @codeCoverageIgnore
            ValueSize::UINT64 => 'P', // @codeCoverageIgnore
        };
    }
}
