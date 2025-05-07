<?php

namespace Throttr\SDK\Requests;

use Throttr\SDK\Enum\TTLType;
use Throttr\SDK\Enum\ValueSize;

/**
 * Insert request
 */
class InsertRequest extends BaseRequest
{
    /**
     * Type
     *
     * @var int
     */
    public int $type = 0x01;

    /**
     * Constructor
     *
     * @param string $key
     * @param int $quota
     * @param TTLType $ttl_type
     * @param int $ttl
     */
    public function __construct(
        public string  $key,
        public int     $quota,
        public TTLType $ttl_type,
        public int     $ttl
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
            pack(static::pack($size), $this->quota) .
            pack(static::pack(ValueSize::UINT8), $this->ttl_type->value) .
            pack(static::pack($size), $this->ttl) .
            pack(static::pack(ValueSize::UINT8), strlen($this->key)) .
            $this->key;
    }
}