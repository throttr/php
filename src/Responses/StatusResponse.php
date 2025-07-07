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
     * @return StatusResponse|null
     */
    public static function fromBytes(string $data, ValueSize $size) : StatusResponse|null {
        $offset = 0;

        // Less than 1 byte? not enough for status.
        if (strlen($data) < 1) return null;

        $status = ord($data[$offset]) === 1;
        return new StatusResponse($data, $status);
    }
}

