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
 * QueryResponse
 */
class QueryResponse extends Response implements IResponse
{
    /**
     * Constructor
     *
     * @param string $data
     * @param bool $status
     * @param int $quota
     * @param TTLType $ttl_type
     * @param int $ttl
     */
    public function __construct(public string $data, public bool $status, public int $quota, public TTLType $ttl_type, public int $ttl)
    {
    }

    /**
     * From bytes
     *
     * @param string $data
     * @param ValueSize $size
     * @return QueryResponse|null
     */
    public static function fromBytes(string $data, ValueSize $size): QueryResponse|null
    {
        $valueSize = $size->value;
        $offset = 0;

        // Less than 1 byte? not enough for status.
        if (strlen($data) < 1) {
            return null;
        }

        $status = ord($data[$offset]) === 1;
        $offset++;

        if ($status) {
            // Less than 1 + N bytes? not enough for quota.
            if (strlen($data) < 1 + $valueSize) {
                return null;
            }

            $quota = unpack(BaseRequest::pack($size), substr($data, $offset, $valueSize))[1];
            $offset += $size->value;

            // Less than 2 + N bytes? not enough for ttl type.
            if (strlen($data) < 2 + $valueSize) {
                return null;
            }

            $ttl_type = TTLType::from(ord($data[$offset]));
            $offset++;

            // Less than 2 + 2N bytes? not enough for ttl.
            if (strlen($data) < 2 + ($valueSize * 2)) {
                return null;
            }

            $ttl = unpack(BaseRequest::pack($size), substr($data, $offset, $valueSize))[1];
            return new QueryResponse($data, true, $quota, $ttl_type, $ttl);
        }

        return new QueryResponse($data, false, 0, TTLType::NANOSECONDS, 0);
    }
}
