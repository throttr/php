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

namespace Throttr\SDK;

/**
 * Pending request
 */
class PendingWrite
{
    /**
     * Constructor
     *
     * @param string $buffer
     * @param array $operations
     */
    public function __construct(public string $buffer, public array $operations)
    {
    }

    /**
     * Buffer
     *
     * @return string
     */
    public function buffer(): string
    {
        return $this->buffer;
    }
}
