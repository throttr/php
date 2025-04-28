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

/**
 * Response
 */
final class Response
{
    /**
     * Can
     *
     * @var bool|null
     */
    private ?bool $can;

    /**
     * Success
     *
     * @var bool|null
     */
    private ?bool $success;

    /**
     * Quota remaining
     *
     * @var int|null
     */
    private ?int $quotaRemaining;

    /**
     * TTL remaining
     *
     * @var int|null
     */
    private ?int $ttlRemaining;

    /**
     * TTP type
     *
     * @var int|null
     */
    private ?int $ttlType;

    /**
     * Constructor
     *
     * @param bool|null $can
     * @param bool|null $success
     * @param int|null $quotaRemaining
     * @param int|null $ttlRemaining
     * @param int|null $ttlType
     */

    private function __construct(
        ?bool $can = null,
        ?bool $success = null,
        ?int $quotaRemaining = null,
        ?int $ttlRemaining = null,
        ?int $ttlType = null
    ) {
        $this->can = $can;
        $this->success = $success;
        $this->quotaRemaining = $quotaRemaining;
        $this->ttlRemaining = $ttlRemaining;
        $this->ttlType = $ttlType;
    }

    /**
     * From bytes
     *
     * @param string $data
     * @return self
     */
    public static function fromBytes(string $data): self
    {
        $length = strlen($data);

        if ($length === 1) {
            // 1 byte response (Update/Purge)
            $success = (ord($data[0]) === 1);
            return new self(success: $success);
        } elseif ($length === 18) {
            // 18 bytes response (Insert/Query)
            $can = (ord($data[0]) === 1);

            $quotaRemaining = self::unpackUint64LE(substr($data, 1, 8));
            $ttlType = ord($data[9]);
            $ttlRemaining = self::unpackInt64LE(substr($data, 10, 8));

            return new self(
                can: $can,
                quotaRemaining: $quotaRemaining,
                ttlRemaining: $ttlRemaining,
                ttlType: $ttlType
            );
        } else {
            throw new \InvalidArgumentException('Invalid response length: ' . $length); // @codeCoverageIgnore
        }
    }


    /**
     * Can
     *
     * @return bool|null
     */
    public function can(): ?bool
    {
        return $this->can;
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
     * Quota remaining
     *
     * @return int|null
     */
    public function quotaRemaining(): ?int
    {
        return $this->quotaRemaining;
    }

    /**
     * TTL remaining
     *
     * @return int|null
     */
    public function ttlRemaining(): ?int
    {
        return $this->ttlRemaining;
    }

    /**
     * Unpack unsigned integer 64 bits little-endian
     *
     * @param string $data
     * @return int
     */
    private static function unpackUint64LE(string $data): int
    {
        [$low, $high] = array_values(unpack('V2', $data));
        return ($high << 32) | $low;
    }

    /**
     * Unpack signed integer 64 bits little-endian
     *
     * @param string $data
     * @return int
     */
    private static function unpackInt64LE(string $data): int
    {
        [$low, $high] = array_values(unpack('V2', $data));
        $value = ($high << 32) | $low;

        if ($high & 0x80000000) {
            $value -= (1 << 64);
        }

        return $value;
    }
}