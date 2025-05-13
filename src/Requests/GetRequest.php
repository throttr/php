<?php

namespace Throttr\SDK\Requests;

use Throttr\SDK\Enum\RequestType;
use Throttr\SDK\Enum\ValueSize;

/**
 * Get request
 */
class GetRequest extends BaseRequest
{
    /**
     * Type
     *
     * @var RequestType
     */
    public RequestType $type = RequestType::GET;

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
        return pack(static::pack(ValueSize::UINT8), $this->type->value) .
            pack(static::pack(ValueSize::UINT8), strlen($this->key)) .
            $this->key;
    }
}
