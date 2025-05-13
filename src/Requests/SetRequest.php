<?php

namespace Throttr\SDK\Requests;

use Throttr\SDK\Enum\RequestType;
use Throttr\SDK\Enum\TTLType;
use Throttr\SDK\Enum\ValueSize;

/**
 * Set request
 */
class SetRequest extends BaseRequest
{
    /**
     * Type
     *
     * @var RequestType
     */
    public RequestType $type = RequestType::SET;

    /**
     * Constructor
     *
     * @param string $key
     * @param TTLType $ttl_type
     * @param int $ttl
     * @param string $value
     */
    public function __construct(
        public string  $key,
        public TTLType $ttl_type,
        public int     $ttl,
        public string $value,
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
        return pack(static::pack(ValueSize::UINT8), $this->type->value) .
            pack(static::pack(ValueSize::UINT8), $this->ttl_type->value) .
            pack(static::pack($size), $this->ttl) .
            pack(static::pack(ValueSize::UINT8), strlen($this->key)) .
            pack(static::pack($size), strlen($this->value)) .
            $this->key .
            $this->value;
    }
}
