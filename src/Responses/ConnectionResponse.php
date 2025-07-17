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
 * ConnectionResponse
 */
class ConnectionResponse extends Response implements IResponse
{
    /**
     * Constructor
     *
     * @param string $data
     * @param bool $status
     * @param array $connection
     */
    public function __construct(public string $data, public bool $status, public array $connection)
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
        "CONNECTIONS" => ValueSize::UINT64,
        "CONNECTION" => ValueSize::UINT64,
        "CHANNELS" => ValueSize::UINT64,
        "CHANNEL" => ValueSize::UINT64,
        "WHOAMI" => ValueSize::UINT64,
    ];

    /**
     * From bytes
     *
     * @param string $data
     * @param ValueSize $size
     * @return ConnectionResponse|null
     */
    public static function fromBytes(string $data, ValueSize $size): ConnectionResponse|null
    {
        $offset = 0;

        $status = ord($data[$offset]) === 1;
        $offset++;

        if ($status) {
            // Less than offset + 16 bytes? not enough for uuid.
            if (strlen($data) < $offset + 16) {
                return null;
            }

            $id = substr($data, $offset, 16);
            $offset += 16;

            // Less than offset + 1 byte? not enough for type.
            if (strlen($data) < $offset + ValueSize::UINT8->value) {
                return null;
            }

            $type = ConnectionType::from(unpack(BaseRequest::pack(ValueSize::UINT8), substr($data, $offset, ValueSize::UINT8->value))[1]);
            $offset += ValueSize::UINT8->value;

            // Less than offset + 1 byte? not enough for kind.
            if (strlen($data) < $offset + ValueSize::UINT8->value) {
                return null;
            }

            $kind = ConnectionKind::from(unpack(BaseRequest::pack(ValueSize::UINT8), substr($data, $offset, ValueSize::UINT8->value))[1]);
            $offset += ValueSize::UINT8->value;

            // Less than offset + 1 byte? not enough for ip version.
            if (strlen($data) < $offset + ValueSize::UINT8->value) {
                return null;
            }

            $ip_version = IpVersion::from(unpack(BaseRequest::pack(ValueSize::UINT8), substr($data, $offset, ValueSize::UINT8->value))[1]);
            $offset += ValueSize::UINT8->value;

            // Less than offset + 16 bytes? not enough for ip.
            if (strlen($data) < $offset + 16) {
                return null;
            }

            $ip = substr($data, $offset, 16);
            $offset += 16;

            // Less than offset + 2 + 8 * 7 bytes? not enough for attributes.
            if (strlen($data) < $offset + ValueSize::UINT16->value + ValueSize::UINT64->value * 7) {
                return null;
            }

            $attributes = ReaderProvider::readIntegers($data, [
                "port" => ValueSize::UINT16,
                "connected_at" => ValueSize::UINT64,
                "read_bytes" => ValueSize::UINT64,
                "write_bytes" => ValueSize::UINT64,
                "published_bytes" => ValueSize::UINT64,
                "received_bytes" => ValueSize::UINT64,
                "allocated_bytes" => ValueSize::UINT64,
                "consumed_bytes" => ValueSize::UINT64,
            ], $offset);

            if (strlen($data) < $offset + ValueSize::UINT64->value * count(static::$types)) {
                return null;
            }

            $requests = ReaderProvider::readIntegers($data, static::$types, $offset);

            return new ConnectionResponse($data, true, array_merge([
                "id" => bin2hex($id),
                "type" => $type,
                "kind" => $kind,
                "ip_version" => $ip_version,
                "ip" => $ip,
                "requests" => $requests,
            ], $attributes));
        }

        return new ConnectionResponse($data, false, []);
    }
}
