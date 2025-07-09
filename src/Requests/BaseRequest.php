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

use Throttr\SDK\Contracts\ShouldDefineSerialization;
use Throttr\SDK\Enum\RequestType;
use Throttr\SDK\Enum\ValueSize;

/**
 * Base request
 */
abstract class BaseRequest implements ShouldDefineSerialization {
    /**
     * Type
     *
     * @var RequestType
     */
    public RequestType $type = RequestType::INSERT;

    /**
     * Pack
     *
     * @param ValueSize $size
     * @return string
     */
    public static function pack(ValueSize $size): string {
        return match ($size) {
            ValueSize::UINT8 => 'C', // @codeCoverageIgnore
            ValueSize::UINT16 => 'v', // @codeCoverageIgnore
            ValueSize::UINT32 => 'V', // @codeCoverageIgnore
            ValueSize::UINT64 => 'P', // @codeCoverageIgnore
        };
    }
}
