<?php

namespace Throttr\SDK\Responses;

use Throttr\SDK\Enum\ValueSize;

/**
 * IResponse
 */
interface IResponse
{
    /**
     * From bytes
     *
     * @param string $data
     * @param ValueSize $size
     * @return IResponse
     */
    public static function fromBytes(string $data, ValueSize $size) : IResponse;
}