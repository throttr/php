<?php

declare(strict_types=1);

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

namespace Throttr\SDK\Enum;

/**
 * TTL type
 */
enum TTLType: int
{
    /**
     * Nanoseconds
     */
    case NANOSECONDS = 0x01;

    /**
     * Microseconds
     */
    case MICROSECONDS = 0x02;

    /**
     * Milliseconds
     */
    case MILLISECONDS = 0x03;

    /**
     * Seconds
     */
    case SECONDS = 0x04;

    /**
     * Minutes
     */
    case MINUTES = 0x05;

    /**
     * Hours
     */
    case HOURS = 0x06;
}
