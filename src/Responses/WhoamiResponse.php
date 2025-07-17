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

/**
 * WhoamiResponse
 */
class WhoamiResponse extends Response implements IResponse
{
    /**
     * Constructor
     *
     * @param string $data
     * @param bool $status
     * @param string $id
     */
    public function __construct(public string $data, public bool $status, public string $id)
    {
    }

    /**
     * From bytes
     *
     * @param string $data
     * @param ValueSize $size
     * @return WhoamiResponse|null
     */
    public static function fromBytes(string $data, ValueSize $size): WhoamiResponse|null
    {
        $offset = 0;

        // Less than 1 byte? not enough for status.
        if (strlen($data) < 17) {
            return null;
        }

        $status = ord($data[$offset]) === 1;

        $id = substr($data, 1);

        return new WhoamiResponse($data, $status, bin2hex($id));
    }
}
