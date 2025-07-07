<?php

namespace Throttr\SDK\Responses;

use Throttr\SDK\Enum\TTLType;
use Throttr\SDK\Enum\ValueSize;
use Throttr\SDK\Requests\BaseRequest;

/**
 * QueryResponse
 */
class QueryResponse extends Response implements IResponse {
    /**
     * Constructor
     *
     * @param string $data
     * @param bool $status
     * @param int $quota
     * @param TTLType $ttl_type
     * @param int $ttl
     */
    public function __construct(public string $data, public bool $status, public int $quota, public TTLType $ttl_type, public int $ttl) {}

    /**
     * From bytes
     *
     * @param string $data
     * @param ValueSize $size
     * @return QueryResponse
     */
    public static function fromBytes(string $data, ValueSize $size) : QueryResponse {
        $valueSize = $size->value;
        $offset = 0;
        $status = ord($data[$offset]) === 1;

        if ($status) {
            $offset++;
            $quota = unpack(BaseRequest::pack($size), substr($data, $offset, $valueSize))[1];
            $offset += $size->value;
            $ttl_type = TTLType::from(ord($data[$offset]));
            $offset ++;
            $ttl = unpack(BaseRequest::pack($size), substr($data, $offset, $valueSize))[1];
            return new QueryResponse($data, true, $quota, $ttl_type, $ttl);
        }

        return new QueryResponse($data, false, 0, TTLType::NANOSECONDS, 0);
    }
}

