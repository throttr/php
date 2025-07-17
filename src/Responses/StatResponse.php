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

use Throttr\SDK\Enum\ValueSize;
use Throttr\SDK\Providers\ReaderProvider;
use Throttr\SDK\Requests\BaseRequest;

/**
 * StatResponse
 */
class StatResponse extends Response implements IResponse
{
    /**
     * Constructor
     *
     * @param string $data
     * @param bool $status
     * @param array $attributes
     */
    public function __construct(public string $data, public bool $status, public array $attributes)
    {
    }

    /**
     * From bytes
     *
     * @param string $data
     * @param ValueSize $size
     * @return StatResponse|null
     */
    public static function fromBytes(string $data, ValueSize $size): StatResponse|null
    {
        $offset = 0;

        $status = ord($data[$offset]) === 1;

        $offset++;

        if ($status) {
            // Less than offset + 32 bytes? not enough for fields.
            if (strlen($data) < $offset + 32) {
                return null;
            }

            $stats = ReaderProvider::readIntegers($data, [
                "reads_per_minute" => ValueSize::UINT64,
                "writes_per_minute" => ValueSize::UINT64,
                "total_reads" => ValueSize::UINT64,
                "total_writes" => ValueSize::UINT64,
            ], $offset);

            return new StatResponse($data, true, $stats);
        }

        return new StatResponse($data, false, []);
    }
}
