<?php

namespace Throttr\SDK\Requests;

use Throttr\SDK\Enum\ValueSize;

/**
 * Query request
 */
class QueryRequest extends BaseRequest
{
    /**
     * Type
     *
     * @var int
     */
    public int $type = 0x02;

    /**
     * Constructor
     *
     * @param string $key
     */
    public function __construct(
        public string $key
    )
    {
    }

    /**
     * To bytes
     *
     * @param ValueSize $size
     * @return string
     */
    public function toBytes(ValueSize $size): string
    {
        return pack(static::pack(ValueSize::UINT8), $this->type) .
            pack(static::pack(ValueSize::UINT8), strlen($this->key)) .
            $this->key;
    }
}
