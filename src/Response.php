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

namespace Throttr\SDK;

use Throttr\SDK\Enum\RequestType;
use Throttr\SDK\Enum\TTLType;
use Throttr\SDK\Enum\ValueSize;
use Throttr\SDK\Requests\BaseRequest;

/**
 * Response
 */
final class Response
{
    /**
     * Success
     *
     * @var bool|null
     */
    private ?bool $success;

    /**
     * Quota
     *
     * @var int|null
     */
    private ?int $quota;

    /**
     * TTL
     *
     * @var int|null
     */
    private ?int $ttl;

    /**
     * TTP type
     *
     * @var int|null
     */
    private ?int $ttlType;


    /**
     * Value
     *
     * @var string|null
     */
    private ?string $value;

    /**
     * Constructor
     *
     * @param bool|null $success
     * @param int|null $quota
     * @param int|null $ttl
     * @param int|null $ttlType
     * @param string|null $value
     */

    private function __construct(
        ?bool $success = null,
        ?int  $quota = null,
        ?int  $ttl = null,
        ?int  $ttlType = null,
        ?string  $value = null,
    ) {
        $this->success = $success;
        $this->quota = $quota;
        $this->ttl = $ttl;
        $this->ttlType = $ttlType;
        $this->value = $value;
    }

    /**
     * From bytes
     *
     * @param string $data
     * @param ValueSize $size
     * @param RequestType $type
     * @return self
     */
    public static function fromBytes(string $data, ValueSize $size, RequestType $type): self
    {
        $length = strlen($data);

        $success = (ord($data[0]) === 1);
        if ($length === 1) {
            return new self(success: $success);
        } else {
            $valueSize = $size->value;
            if ($type === RequestType::QUERY) {
                $quota = unpack(BaseRequest::pack($size), substr($data, 1, $valueSize))[1];

                $ttlTypeOffset = 1 + $valueSize;
                $ttlType = ord($data[$ttlTypeOffset]);

                $ttlOffset = $ttlTypeOffset + 1;
                $ttl = unpack(BaseRequest::pack($size), substr($data, $ttlOffset, $valueSize))[1];

                return new self(
                    success: $success,
                    quota: $quota,
                    ttl: $ttl,
                    ttlType: $ttlType
                );
            } else {
                $offset = 1;
                $ttlType = ord($data[$offset]);
                $offset += 1;
                $ttl = unpack(BaseRequest::pack($size), substr($data, $offset, $valueSize))[1];
                $offset += $valueSize * 2;
                $value = substr($data, $offset);

                return new self(
                    success: $success,
                    ttl: $ttl,
                    ttlType: $ttlType,
                    value: $value,
                );
            }
        }
    }

    /**
     * Success
     *
     * @return bool|null
     */
    public function success(): ?bool
    {
        return $this->success;
    }

    /**
     * Quota
     *
     * @return int|null
     */
    public function quota(): ?int
    {
        return $this->quota;
    }

    /**
     * TTL
     *
     * @return int|null
     */
    public function ttl(): ?int
    {
        return $this->ttl;
    }



    /**
     * TTL type
     *
     * @return TTLType|null
     */
    public function ttlType(): ?TTLType
    {
        return $this->ttlType !== null ? TTLType::from($this->ttlType) : null;
    }

    /**
     * Value
     *
     * @return string|null
     */
    public function value(): ?string
    {
        return $this->value;
    }
}
