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

use Throttr\SDK\Enum\ConnectionKind;
use Throttr\SDK\Enum\ConnectionType;
use Throttr\SDK\Enum\IpVersion;
use Throttr\SDK\Enum\KeyType;
use Throttr\SDK\Enum\TTLType;
use Throttr\SDK\Enum\ValueSize;
use Throttr\SDK\Providers\ReaderProvider;
use Throttr\SDK\Requests\BaseRequest;

/**
 * ConnectionsResponse
 */
class ConnectionsResponse extends Response implements IResponse
{
    /**
     * Constructor
     *
     * @param string $data
     * @param bool $status
     * @param array $connections
     */
    public function __construct(public string $data, public bool $status, public array $connections)
    {
    }

    public static array $types = [
        "INSERT" => ValueSize::UINT64,
        "SET" => ValueSize::UINT64,
        "QUERY" => ValueSize::UINT64,
        "GET" => ValueSize::UINT64,
        "UPDATE" => ValueSize::UINT64,
        "PURGE" => ValueSize::UINT64,
        "LIST" => ValueSize::UINT64,
        "INFO" => ValueSize::UINT64,
        "STAT" => ValueSize::UINT64,
        "STATS" => ValueSize::UINT64,
        "PUBLISH" => ValueSize::UINT64,
        "SUBSCRIBE" => ValueSize::UINT64,
        "UNSUBSCRIBE" => ValueSize::UINT64,
        "CONNECTION" => ValueSize::UINT64,
        "CONNECTIONS" => ValueSize::UINT64,
        "CHANNELS" => ValueSize::UINT64,
        "CHANNEL" => ValueSize::UINT64,
        "WHOAMI" => ValueSize::UINT64
    ];

    /**
     * From bytes
     *
     * @param string $data
     * @param ValueSize $size
     * @return ListResponse|null
     */
    public static function fromBytes(string $data, ValueSize $size): ConnectionsResponse|null
    {
        $offset = 0;

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
                return new ConnectionsResponse($data, true, []);
            }

            $connections = [];

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

                $number_of_connections = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
                $offset += ValueSize::UINT64->value;

                $expected_fragment_size = (
                    16 + // id
                    ValueSize::UINT8->value * 3 + // type, kind and ip version
                    16 +  // ip
                    ValueSize::UINT16->value + // port
                    (
                        ValueSize::UINT64->value * (
                            6 + // connected_at, read_bytes, write_bytes, published_bytes, received_bytes, allocated_bytes
                            count(static::$types) // per request type metrics
                        )
                    )
                ) * $number_of_connections;

                if (strlen($data) < $offset + $expected_fragment_size) {
                    return null;
                }

                // Per connection in fragment
                for ($e = 0; $e < $number_of_connections; ++$e) {
                    $id = substr($data, $offset, 16);
                    $offset += 16;

                    $type = ConnectionType::from(unpack(BaseRequest::pack(ValueSize::UINT8), substr($data, $offset, ValueSize::UINT8->value))[1]);
                    $offset += ValueSize::UINT8->value;

                    $kind = ConnectionKind::from(unpack(BaseRequest::pack(ValueSize::UINT8), substr($data, $offset, ValueSize::UINT8->value))[1]);
                    $offset += ValueSize::UINT8->value;

                    $ip_version = IpVersion::from(unpack(BaseRequest::pack(ValueSize::UINT8), substr($data, $offset, ValueSize::UINT8->value))[1]);
                    $offset += ValueSize::UINT8->value;

                    $ip = substr($data, $offset, 16);
                    $offset += 16;

                    $attributes = ReaderProvider::readIntegers($data, [
                        "port" => ValueSize::UINT16,
                        "connected_at" => ValueSize::UINT64,
                        "read_bytes" => ValueSize::UINT64,
                        "write_bytes" => ValueSize::UINT64,
                        "published_bytes" => ValueSize::UINT64,
                        "received_bytes" => ValueSize::UINT64,
                        "allocated_bytes" => ValueSize::UINT64,
                    ], $offset);

                    $requests = ReaderProvider::readIntegers($data, static::$types, $offset);

                    $connections[] = array_merge([
                        "id" => bin2hex($id),
                        "type" => $type,
                        "kind" => $kind,
                        "ip_version" => $ip_version,
                        "ip" => $ip,
                        "requests" => $requests,
                    ], $attributes);
                }
            }

            return new ConnectionsResponse($data, true, $connections);
        }

        return new ConnectionsResponse($data, false, []);
    }
}
