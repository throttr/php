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
 * InfoResponse
 */
class InfoResponse extends Response implements IResponse
{
    public static array $types = [
        'INSERT',
        'QUERY',
        'UPDATE',
        'PURGE',
        'GET',
        'SET',
        'LIST',
        'INFO',
        'STATS',
        'STAT',
        'SUBSCRIBE',
        'UNSUBSCRIBE',
        'PUBLISH',
        'CHANNEL',
        'CHANNELS',
        'WHOAMI',
        'CONNECTION',
        'CONNECTIONS',
    ];

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
     * @return InfoResponse|null
     */
    public static function fromBytes(string $data, ValueSize $size): InfoResponse|null
    {
        $offset = 0;

        if (strlen($data) < 433) {
            return null;
        }

        $status = ord($data[$offset]) === 1;
        $offset++;

        if ($status) {
            $header = ReaderProvider::readIntegers($data, [
                "now" => ValueSize::UINT64,
                "total_requests" => ValueSize::UINT64,
                "total_requests_per_minute" => ValueSize::UINT64,
            ], $offset);

            $types = [];

            foreach (static::$types as $key) {
                $types[$key] = ReaderProvider::readIntegers($data, [
                    "total" => ValueSize::UINT64,
                    "per_minute" => ValueSize::UINT64,
                ], $offset);
            }

            $attributes = ReaderProvider::readIntegers($data, [
                "total_read" => ValueSize::UINT64,
                "total_read_per_minute" => ValueSize::UINT64,
                "total_write" => ValueSize::UINT64,
                "total_write_per_minute" => ValueSize::UINT64,
                "total_keys" => ValueSize::UINT64,
                "total_counters" => ValueSize::UINT64,
                "total_buffers" => ValueSize::UINT64,
                "total_allocated_bytes_on_counters" => ValueSize::UINT64,
                "total_allocated_bytes_on_buffers" => ValueSize::UINT64,
                "total_subscriptions" => ValueSize::UINT64,
                "total_channels" => ValueSize::UINT64,
                "started_at" => ValueSize::UINT64,
                "total_connections" => ValueSize::UINT64,
            ], $offset);

            $version = substr($data, $offset, 16);

            return new InfoResponse($data, true, array_merge($header, [
                "requests" => $types,
                "version" => $version,
            ], $attributes));
        }

        return new InfoResponse($data, false, []);
    }
}
