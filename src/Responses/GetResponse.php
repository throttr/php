<?php

namespace Throttr\SDK\Responses;

use Throttr\SDK\Enum\TTLType;
use Throttr\SDK\Enum\ValueSize;
use Throttr\SDK\Requests\BaseRequest;

/**
 * GetResponse
 */
class GetResponse extends Response implements IResponse {
    /**
     * Constructor
     *
     * @param string $data
     * @param bool $status
     * @param TTLType $ttl_type
     * @param int $ttl
     * @param string $value
     */
    public function __construct(public string $data, public bool $status,public TTLType $ttl_type, public int $ttl, public string $value) {}

    /**
     * From bytes
     *
     * @param string $data
     * @param ValueSize $size
     * @return GetResponse
     */
    public static function fromBytes(string $data, ValueSize $size) : GetResponse {
        $valueSize = $size->value;
        $offset = 0;
        $status = ord($data[$offset]) === 1;
        $offset++;
        $ttl_type = TTLType::from(ord($data[$offset]));
        $offset++;
        $ttl = unpack(BaseRequest::pack($size), substr($data, $offset, $valueSize))[1];
        $offset += $valueSize;
        $value_sized = unpack(BaseRequest::pack($size), substr($data, $offset, $valueSize))[1];
        $offset += $valueSize;
        $value = substr($data, $offset, $value_sized);
        return new GetResponse($data, $status, $ttl_type, $ttl, $value);
    }
}

