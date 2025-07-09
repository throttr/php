<?php

// Copyright (C) 2025 Ian Torres
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program. If not, see <https://www.gnu.org/licenses/>.

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
     * @return GetResponse|null
     */
    public static function fromBytes(string $data, ValueSize $size) : GetResponse|null {
        $valueSize = $size->value;
        $offset = 0;

        // Less than 1 byte? not enough for status.
        if (strlen($data) < 1) return null;

        $status = ord($data[$offset]) === 1;
        $offset++;

        if ($status) {
            // Less than 2 bytes? not enough for ttl type.
            if (strlen($data) < 2) return null;

            $ttl_type = TTLType::from(ord($data[$offset]));
            $offset++;

            // Less than 2 + N bytes? not enough for ttl.
            if (strlen($data) < 2 + $valueSize) return null;

            $ttl = unpack(BaseRequest::pack($size), substr($data, $offset, $valueSize))[1];
            $offset += $valueSize;

            // Less than 2 + 2 * N bytes? not enough for value size.
            if (strlen($data) < 2 + ($valueSize * 2)) return null;

            $value_sized = unpack(BaseRequest::pack($size), substr($data, $offset, $valueSize))[1];
            $offset += $valueSize;

            // Less than 2 + 2 * N + O bytes? not enough for value.
            if (strlen($data) < 2 + ($valueSize * 2) + $value_sized) return null;

            $value = substr($data, $offset, $value_sized);
            return new GetResponse($data, true, $ttl_type, $ttl, $value);
        }

        return new GetResponse($data, false, TTLType::NANOSECONDS, 0, "");
    }
}

