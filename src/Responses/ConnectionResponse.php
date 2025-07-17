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
        "INSERT",
        "SET",
        "QUERY",
        "GET",
        "UPDATE",
        "PURGE",
        "LIST",
        "INFO",
        "STAT",
        "STATS",
        "PUBLISH",
        "SUBSCRIBE",
        "UNSUBSCRIBE",
        "CONNECTIONS",
        "CONNECTION",
        "CHANNELS",
        "CHANNEL",
        "WHOAMI"
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

        // Less than 1 byte? not enough for status.
        if (strlen($data) < 1) {
            return null;
        }

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

            // Less than offset + 2 bytes? not enough for port.
            if (strlen($data) < $offset + ValueSize::UINT16->value) {
                return null;
            }

            $port = unpack(BaseRequest::pack(ValueSize::UINT16), substr($data, $offset, ValueSize::UINT16->value))[1];
            $offset += ValueSize::UINT16->value;

            // Less than offset + 8 bytes? not enough for connected at.
            if (strlen($data) < $offset + ValueSize::UINT64->value) {
                return null;
            }

            $connected_at = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += ValueSize::UINT64->value;

            // Less than offset + 8 bytes? not enough for read bytes.
            if (strlen($data) < $offset + ValueSize::UINT64->value) {
                return null;
            }

            $ready_bytes = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += ValueSize::UINT64->value;

            // Less than offset + 8 bytes? not enough for write bytes.
            if (strlen($data) < $offset + ValueSize::UINT64->value) {
                return null;
            }

            $write_bytes = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += ValueSize::UINT64->value;

            // Less than offset + 8 bytes? not enough for published bytes.
            if (strlen($data) < $offset + ValueSize::UINT64->value) {
                return null;
            }

            $published_bytes = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += ValueSize::UINT64->value;

            // Less than offset + 8 bytes? not enough for received bytes.
            if (strlen($data) < $offset + ValueSize::UINT64->value) {
                return null;
            }

            $received_bytes = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += ValueSize::UINT64->value;

            // Less than offset + 8 bytes? not enough for allocated bytes.
            if (strlen($data) < $offset + ValueSize::UINT64->value) {
                return null;
            }

            $allocated_bytes = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += ValueSize::UINT64->value;

            // Less than offset + 8 bytes? not enough for consumed bytes.
            if (strlen($data) < $offset + ValueSize::UINT64->value) {
                return null;
            }

            $consumed_bytes = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += ValueSize::UINT64->value;

            $requests = [];
            foreach (static::$types as $request_type) {
                // Less than offset + 8 bytes? not enough for requests metric.
                if (strlen($data) < $offset + ValueSize::UINT64->value) {
                    return null;
                }
                $requests[$request_type] = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
                $offset += ValueSize::UINT64->value;
            }

            return new ConnectionResponse($data, true, [
                "id" => bin2hex($id),
                "type" => $type,
                "kind" => $kind,
                "ip_version" => $ip_version,
                "ip" => $ip,
                "port" => $port,
                "connected_at" => $connected_at,
                "read_bytes" => $ready_bytes,
                "write_bytes" => $write_bytes,
                "published_bytes" => $published_bytes,
                "received_bytes" => $received_bytes,
                "allocated_bytes" => $allocated_bytes,
                "consumed_bytes" => $consumed_bytes,
                "requests" => $requests,
            ]);
        }

        return new ConnectionResponse($data, false, []);
    }
}
