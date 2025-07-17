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
class ChannelsResponse extends Response implements IResponse
{
    /**
     * Constructor
     *
     * @param string $data
     * @param bool $status
     * @param array $channels
     */
    public function __construct(public string $data, public bool $status, public array $channels)
    {
    }

    /**
     * From bytes
     *
     * @param string $data
     * @param ValueSize $size
     * @return ChannelsResponse|null
     */
    public static function fromBytes(string $data, ValueSize $size): ChannelsResponse|null
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
            if (strlen($data) < 1 + 8) {
                return null;
            }

            $fragments = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += ValueSize::UINT64->value;

            if ($fragments === 0) {
                return new ChannelsResponse($data, true, []);
            }

            $channels_container = [];

            for ($i = 0; $i < $fragments; ++$i) {
                // Less than offset + 8 bytes? not enough for fragment index.
                if (strlen($data) < $offset + ValueSize::UINT64->value) {
                    return null;
                }

                $fragment = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
                $offset += ValueSize::UINT64->value;

                // Less than offset + 8 bytes? not enough for fragment keys count.
                if (strlen($data) < $offset + ValueSize::UINT64->value) {
                    return null;
                }

                $number_of_channels = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
                $offset += ValueSize::UINT64->value;

                $channels_in_fragment = [];

                // Per key in fragment
                for ($e = 0; $e < $number_of_channels; ++$e) {
                    // Less than offset + 1 byte? not enough for key size.
                    if (strlen($data) < $offset + ValueSize::UINT8->value) {
                        return null;
                    }

                    $channel_size = unpack(BaseRequest::pack(ValueSize::UINT8), substr($data, $offset, ValueSize::UINT8->value))[1];
                    $offset += ValueSize::UINT8->value;

                    // Less than offset + 8 bytes? not enough for read bytes.
                    if (strlen($data) < $offset + ValueSize::UINT64->value) {
                        return null;
                    }

                    $read_bytes = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
                    $offset += ValueSize::UINT64->value;

                    // Less than offset + 8 bytes? not enough for write bytes.
                    if (strlen($data) < $offset + ValueSize::UINT64->value) {
                        return null;
                    }

                    $write_bytes = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
                    $offset += ValueSize::UINT64->value;

                    // Less than offset + 8 bytes? not enough for subscriptions.
                    if (strlen($data) < $offset + ValueSize::UINT64->value) {
                        return null;
                    }

                    $subscriptions = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
                    $offset += ValueSize::UINT64->value;

                    $channels_in_fragment[] = [
                        "size" => $channel_size,
                        "read_bytes" => $read_bytes,
                        "write_bytes" => $write_bytes,
                        "subscriptions" => $subscriptions,
                    ];
                }

                $total = array_sum(array_column($channels_in_fragment, 'size'));

                // Less than offset + total channels bytes? not enough for name parsing
                if (strlen($data) < $offset + $total) {
                    return null;
                }

                for ($e = 0; $e < $number_of_channels; ++$e) {
                    $channels_in_fragment[$e]["name"] = substr($data, $offset, $channels_in_fragment[$e]["size"]);
                    $offset += $channels_in_fragment[$e]["size"];
                    unset($channels_in_fragment[$e]["size"]);
                }

                $channels_container = array_merge($channels_container, $channels_in_fragment);
            }

            return new ChannelsResponse($data, true, $channels_container);
        }

        return new ChannelsResponse($data, false, []);
    }
}
