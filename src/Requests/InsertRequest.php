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

namespace Throttr\SDK\Requests;

use Throttr\SDK\Enum\RequestType;
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
     * @var RequestType
     */
    public RequestType $type = RequestType::INSERT;

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
    ) {
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
            pack(static::pack($size), $this->quota) .
            pack(static::pack(ValueSize::UINT8), $this->ttl_type->value) .
            pack(static::pack($size), $this->ttl) .
            pack(static::pack(ValueSize::UINT8), strlen($this->key)) .
            $this->key;
    }
}
