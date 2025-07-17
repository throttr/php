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

namespace Throttr\SDK\Providers;

use Throttr\SDK\Requests\BaseRequest;

class ReaderProvider
{
    /**
     * Read integers
     *
     * @param string $data
     * @param array $columns
     * @param int $offset
     * @return array
     */
    public static function readIntegers(string $data, array $columns, int &$offset): array
    {
        $result = [];

        foreach ($columns as $column => $size) {
            $result[$column] = unpack(BaseRequest::pack($size), substr($data, $offset, $size->value))[1];
            $offset += $size->value;
        }

        return $result;
    }
}
