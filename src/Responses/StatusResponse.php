<?php

namespace Throttr\SDK\Responses;

use Throttr\SDK\Enum\ValueSize;

class StatusResponse extends Response implements IResponse {

    /**
     * Constructor
     *
     * @param string $data
     * @param bool $status
     */
    public function __construct(public string $data, public bool $status) {
    }

    /**
     * From bytes
     *
     * @param string $data
     * @param ValueSize $size
     * @return StatusResponse
     */
    public static function fromBytes(string $data, ValueSize $size) : StatusResponse {
        $offset = 0;
        $status = ord($data[$offset]) === 1;
        return new StatusResponse($data, $status);
    }
}

