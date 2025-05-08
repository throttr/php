<?php

namespace Throttr\SDK\Requests;

use Throttr\SDK\Enum\ValueSize;

/**
 * Purge request
 */
class PurgeRequest extends BaseRequest
{
    /**
     * Type
     *
     * @var int
     */
    public int $type = 0x04;

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
