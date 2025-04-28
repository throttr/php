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
use Throttr\SDK\Enum\AttributeType;
use Throttr\SDK\Enum\ChangeType;

/**
 * Request
 */
final class Request
{
    /**
     * Request type
     *
     * @var RequestType
     */
    private RequestType $requestType;

    /**
     * Quota
     *
     * @var int|null
     */
    private ?int $quota;

    /**
     * Usage
     *
     * @var int|null
     */
    private ?int $usage;

    /**
     * TTL type
     *
     * @var TTLType|null
     */
    private ?TTLType $ttlType;

    /**
     * TTL
     *
     * @var int|null
     */
    private ?int $ttl;

    /**
     * Attribute type
     *
     * @var AttributeType|null
     */
    private ?AttributeType $attribute;

    /**
     * Change type
     *
     * @var ChangeType|null
     */
    private ?ChangeType $change;

    /**
     * Value
     *
     * @var int|null
     */
    private ?int $value;

    /**
     * Consumer ID
     *
     * @var string|null
     */
    private ?string $consumerId;

    /**
     * Resource ID
     *
     * @var string|null
     */
    private ?string $resourceId;

    /**
     * Constructor
     *
     * @param RequestType $requestType
     * @param int|null $quota
     * @param int|null $usage
     * @param TTLType|null $ttlType
     * @param int|null $ttl
     * @param AttributeType|null $attribute
     * @param ChangeType|null $change
     * @param int|null $value
     * @param string|null $consumerId
     * @param string|null $resourceId
     */
    public function __construct(
        RequestType $requestType,
        ?int $quota = null,
        ?int $usage = null,
        ?TTLType $ttlType = null,
        ?int $ttl = null,
        ?AttributeType $attribute = null,
        ?ChangeType $change = null,
        ?int $value = null,
        ?string $consumerId = null,
        ?string $resourceId = null
    ) {
        $this->requestType = $requestType;
        $this->quota = $quota;
        $this->usage = $usage;
        $this->ttlType = $ttlType;
        $this->ttl = $ttl;
        $this->attribute = $attribute;
        $this->change = $change;
        $this->value = $value;
        $this->consumerId = $consumerId;
        $this->resourceId = $resourceId;
    }

    /**
     * Serialize insert
     *
     * @return string
     */
    private function serializeInsert(): string
    {
        if ($this->quota === null || $this->usage === null || $this->ttlType === null || $this->ttl === null || $this->consumerId === null || $this->resourceId === null) {
            throw new \InvalidArgumentException('Missing fields for insert request.'); // @codeCoverageIgnore
        }

        $buffer = '';
        $buffer .= pack('C', $this->requestType->value); // request_type (1 byte)
        $buffer .= $this->packUint64LE($this->quota);     // quota (8 bytes, little endian)
        $buffer .= $this->packUint64LE($this->usage);     // usage (8 bytes, little endian)
        $buffer .= pack('C', $this->ttlType->value);       // ttl_type (1 byte)
        $buffer .= $this->packUint64LE($this->ttl);        // ttl (8 bytes, little endian)
        $buffer .= pack('C', strlen($this->consumerId));   // consumer_id_size (1 byte)
        $buffer .= pack('C', strlen($this->resourceId));   // resource_id_size (1 byte)
        $buffer .= $this->consumerId;                      // consumer_id (N bytes)
        $buffer .= $this->resourceId;                      // resource_id (M bytes)

        return $buffer;
    }

    /**
     * Serialize query or purge
     *
     * @return string
     */
    private function serializeQueryOrPurge(): string
    {
        if ($this->consumerId === null || $this->resourceId === null) {
            throw new \InvalidArgumentException('Missing fields for query/purge request.'); // @codeCoverageIgnore
        }

        $buffer = '';
        $buffer .= pack('C', $this->requestType->value); // request_type (1 byte)
        $buffer .= pack('C', strlen($this->consumerId)); // consumer_id_size (1 byte)
        $buffer .= pack('C', strlen($this->resourceId)); // resource_id_size (1 byte)
        $buffer .= $this->consumerId;                    // consumer_id (N bytes)
        $buffer .= $this->resourceId;                    // resource_id (M bytes)

        return $buffer;
    }

    /**
     * Serialize update
     *
     * @return string
     */
    private function serializeUpdate(): string
    {
        if ($this->attribute === null || $this->change === null || $this->value === null || $this->consumerId === null || $this->resourceId === null) {
            throw new \InvalidArgumentException('Missing fields for update request.'); // @codeCoverageIgnore
        }

        $buffer = '';
        $buffer .= pack('C', $this->requestType->value); // request_type (1 byte)
        $buffer .= pack('C', $this->attribute->value);   // attribute (1 byte)
        $buffer .= pack('C', $this->change->value);      // change (1 byte)
        $buffer .= $this->packUint64LE($this->value);    // value (8 bytes, little endian)
        $buffer .= pack('C', strlen($this->consumerId)); // consumer_id_size (1 byte)
        $buffer .= pack('C', strlen($this->resourceId)); // resource_id_size (1 byte)
        $buffer .= $this->consumerId;                    // consumer_id (N bytes)
        $buffer .= $this->resourceId;                    // resource_id (M bytes)

        return $buffer;
    }

    /**
     * Pack unsigned integer 64 bits little-endian
     *
     * @param int $value
     * @return string
     */
    private function packUint64LE(int $value): string
    {
        $low = $value & 0xFFFFFFFF;
        $high = ($value >> 32) & 0xFFFFFFFF;
        return pack('V2', $low, $high);
    }

    /**
     * To bytes
     *
     * @return string
     */
    public function toBytes(): string
    {
        return match ($this->requestType) {
            RequestType::INSERT => $this->serializeInsert(),
            RequestType::QUERY, RequestType::PURGE => $this->serializeQueryOrPurge(),
            RequestType::UPDATE => $this->serializeUpdate(),
        };
    }
}
