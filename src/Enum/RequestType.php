<?php declare(strict_types=1);

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
 * Request type
 */
enum RequestType: int
{
    /**
     * Insert
     */
    case INSERT = 0x01;

    /**
     * Query
     */
    case QUERY = 0x02;

    /**
     * Update
     */
    case UPDATE = 0x03;

    /**
     * Purge
     */
    case PURGE = 0x04;

    /**
     * Set
     */
    case SET = 0x05;

    /**
     * Get
     */
    case GET = 0x06;

    /**
     * LIST
     */
    case LIST = 0x07;

    /**
     * INFO
     */
    case INFO = 0x08;

    /**
     * STAT
     */
    case STAT = 0x09;

    /**
     * STATS
     */
    case STATS = 0x10;

    /**
     * SUBSCRIBE
     */
    case SUBSCRIBE = 0x11;

    /**
     * UNSUBSCRIBE
     */
    case UNSUBSCRIBE = 0x12;

    /**
     * PUBLISH
     */
    case PUBLISH = 0x13;

    /**
     * CONNECTIONS
     */
    case CONNECTIONS = 0x14;

    /**
     * CONNECTION
     */
    case CONNECTION = 0x15;

    /**
     * CHANNELS
     */
    case CHANNELS = 0x16;

    /**
     * CHANNEL
     */
    case CHANNEL = 0x17;

    /**
     * WHOAMI
     */
    case WHOAMI = 0x18;

    /**
     * EVENT
     */
    case EVENT = 0x19;
}
