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

use Throttr\SDK\Enum\ValueSize;

class StatusResponse extends Response implements IResponse
{
    /**
     * Constructor
     *
     * @param string $data
     * @param bool $status
     */
    public function __construct(public string $data, public bool $status)
    {
    }

    /**
     * From bytes
     *
     * @param string $data
     * @param ValueSize $size
     * @return StatusResponse|null
     */
    public static function fromBytes(string $data, ValueSize $size): StatusResponse|null
    {
        return new StatusResponse($data, ord($data[0]) === 1);
    }
}
