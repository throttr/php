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
use Throttr\SDK\Requests\BaseRequest;

/**
 * ChannelsResponse
 */
class ChannelResponse extends Response implements IResponse {
    /**
     * Constructor
     *
     * @param string $data
     * @param bool $status
     * @param array $subscribers
     */
    public function __construct(public string $data, public bool $status, public array $subscribers) {}

    /**
     * From bytes
     *
     * @param string $data
     * @param ValueSize $size
     * @return ChannelsResponse|null
     */
    public static function fromBytes(string $data, ValueSize $size) : ChannelResponse|null {
        $valueSize = $size->value;
        $offset = 0;

        // Less than 1 byte? not enough for status.
        if (strlen($data) < 1) return null;

        $status = ord($data[$offset]) === 1;
        $offset++;

        if ($status) {
            // Less than 1 + N bytes? not enough for number of subscribers.
            if (strlen($data) < 1 + 8) return null;

            $subscribers = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += ValueSize::UINT64->value;

            if ($subscribers === 0) return new ChannelResponse($data, true, []);

            $subscribers_container = [];

            for ($i = 0; $i < $subscribers; ++$i) {
                // Less than offset + 16 bytes? not enough for connection id.
                if (strlen($data) < $offset + 16) return null;

                $id = substr($data, $offset, 16);
                $offset += 16;

                // Less than offset + 8 bytes? not enough for subscribed at.
                if (strlen($data) < $offset + ValueSize::UINT64->value) return null;

                $subscribed_at = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
                $offset += ValueSize::UINT64->value;

                // Less than offset + 8 bytes? not enough for read bytes.
                if (strlen($data) < $offset + ValueSize::UINT64->value) return null;

                $read_bytes = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
                $offset += ValueSize::UINT64->value;

                // Less than offset + 8 bytes? not enough for write bytes.
                if (strlen($data) < $offset + ValueSize::UINT64->value) return null;

                $write_bytes = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
                $offset += ValueSize::UINT64->value;

                $subscribers_container[] = [
                    "id" => bin2hex($id),
                    "subscribed_at" => $subscribed_at,
                    "read_bytes" => $read_bytes,
                    "write_bytes" => $write_bytes,
                ];
            }

            return new ChannelResponse($data, true, $subscribers_container);
        }

        return new ChannelResponse($data, false, []);
    }
}

