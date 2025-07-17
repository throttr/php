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
 * ChannelsResponse
 */
class ChannelResponse extends Response implements IResponse
{
    /**
     * Constructor
     *
     * @param string $data
     * @param bool $status
     * @param array $subscribers
     */
    public function __construct(public string $data, public bool $status, public array $subscribers)
    {
    }

    /**
     * From bytes
     *
     * @param string $data
     * @param ValueSize $size
     * @return ChannelsResponse|null
     */
    public static function fromBytes(string $data, ValueSize $size): ChannelResponse|null
    {
        $offset = 0;

        $status = ord($data[$offset]) === 1;
        $offset++;

        if ($status) {
            // Less than 8 bytes? not enough for status and number of subscribers.
            if (strlen($data) < $offset + 8) {
                return null;
            }

            $subscribers = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += ValueSize::UINT64->value;

            $subscribers_container = [];

            if (strlen($data) < $offset + (16 + ValueSize::UINT64->value * 3) * $subscribers) {
                return null;
            }

            for ($i = 0; $i < $subscribers; ++$i) {
                $id = substr($data, $offset, 16);
                $offset += 16;

                $values = ReaderProvider::readIntegers($data, [
                    "subscribed_at" => ValueSize::UINT64,
                    "read_bytes" => ValueSize::UINT64,
                    "write_bytes" => ValueSize::UINT64,
                ], $offset);

                $subscribers_container[] = array_merge(
                    ["id" => bin2hex($id)],
                    $values
                );
            }

            return new ChannelResponse($data, true, $subscribers_container);
        }

        return new ChannelResponse($data, false, []);
    }
}
