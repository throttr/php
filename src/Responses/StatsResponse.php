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

use Throttr\SDK\Enum\KeyType;
use Throttr\SDK\Enum\TTLType;
use Throttr\SDK\Enum\ValueSize;
use Throttr\SDK\Providers\ReaderProvider;
use Throttr\SDK\Requests\BaseRequest;

/**
 * StatsResponse
 */
class StatsResponse extends Response implements IResponse
{
    /**
     * Constructor
     *
     * @param string $data
     * @param bool $status
     * @param array $keys
     */
    public function __construct(public string $data, public bool $status, public array $keys)
    {
    }

    /**
     * From bytes
     *
     * @param string $data
     * @param ValueSize $size
     * @return StatsResponse|null
     */
    public static function fromBytes(string $data, ValueSize $size): StatsResponse|null
    {
        $offset = 0;

        // Less than 1 byte? not enough for status.
        if (strlen($data) < 1) {
            return null;
        }

        $status = ord($data[$offset]) === 1;
        $offset++;

        if ($status) {
            // Less than 1 + N bytes? not enough for quota.
            if (strlen($data) < 1 + 8) {
                return null;
            }

            $fragments = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += ValueSize::UINT64->value;

            if ($fragments === 0) {
                return new StatsResponse($data, true, []);
            }

            $keys_container = [];

            for ($i = 0; $i < $fragments; ++$i) {
                // Less than offset + 8 bytes? not enough for fragment index.
                if (strlen($data) < $offset + ValueSize::UINT64->value) {
                    return null;
                }

                $offset += ValueSize::UINT64->value;

                // Less than offset + 8 bytes? not enough for fragment keys count.
                if (strlen($data) < $offset + ValueSize::UINT64->value) {
                    return null;
                }

                $number_of_keys = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
                $offset += ValueSize::UINT64->value;

                $keys_in_fragment = [];

                $expected_fragment_size = (
                    ValueSize::UINT8->value + // key size
                        ValueSize::UINT64->value * 4 // reads_per_minute, writes_per_minute, total_reads, total_writes
                ) * $number_of_keys;

                if (strlen($data) < $offset + $expected_fragment_size) {
                    return null;
                }

                // Per key in fragment
                for ($e = 0; $e < $number_of_keys; ++$e) {
                    $keys_in_fragment[] = ReaderProvider::readIntegers($data, [
                        "size" =>  ValueSize::UINT8,
                        "reads_per_minute" =>  ValueSize::UINT64,
                        "writes_per_minute" =>  ValueSize::UINT64,
                        "total_reads" =>  ValueSize::UINT64,
                        "total_writes" =>  ValueSize::UINT64,
                    ], $offset);
                }

                $total = array_sum(array_column($keys_in_fragment, 'size'));

                // Less than offset + total keys bytes? not enough for name parsing
                if (strlen($data) < $offset + $total) {
                    return null;
                }

                for ($e = 0; $e < $number_of_keys; ++$e) {
                    $keys_in_fragment[$e]["key"] = substr($data, $offset, $keys_in_fragment[$e]["size"]);
                    $offset += $keys_in_fragment[$e]["size"];
                    unset($keys_in_fragment[$e]["size"]);
                }

                $keys_container = array_merge($keys_container, $keys_in_fragment);
            }

            return new StatsResponse($data, true, $keys_container);
        }

        return new StatsResponse($data, false, []);
    }
}
