<?php

namespace Throttr\SDK\Requests;

use Throttr\SDK\Enum\AttributeType;
use Throttr\SDK\Enum\ChangeType;
use Throttr\SDK\Enum\RequestType;
use Throttr\SDK\Enum\TTLType;
use Throttr\SDK\Enum\ValueSize;

/**
 * Update request
 */
class UpdateRequest extends BaseRequest
{
    /**
     * Type
     *
     * @var RequestType
     */
    public RequestType $type = RequestType::UPDATE;

    /**
     * Constructor
     *
     * @param AttributeType $attribute
     * @param ChangeType $change
     * @param int $value
     * @param string $key
     */
    public function __construct(
        public AttributeType $attribute,
        public ChangeType    $change,
        public int           $value,
        public string        $key
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
            pack(static::pack(ValueSize::UINT8), $this->attribute->value) .
            pack(static::pack(ValueSize::UINT8), $this->change->value) .
            pack(static::pack($size), $this->value) .
            pack(static::pack(ValueSize::UINT8), strlen($this->key)) .
            $this->key;
    }
}
