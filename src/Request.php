<?php declare(strict_types=1);

namespace Throttr\SDK;

use Throttr\SDK\Enum\RequestType;
use Throttr\SDK\Enum\TTLType;
use Throttr\SDK\Enum\AttributeType;
use Throttr\SDK\Enum\ChangeType;

final class Request
{
    private RequestType $requestType;
    private ?int $quota;
    private ?int $usage;
    private ?TTLType $ttlType;
    private ?int $ttl;
    private ?AttributeType $attribute;
    private ?ChangeType $change;
    private ?int $value;
    private ?string $consumerId;
    private ?string $resourceId;

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

    private function serializeInsert(): string
    {
        if ($this->quota === null || $this->usage === null || $this->ttlType === null || $this->ttl === null || $this->consumerId === null || $this->resourceId === null) {
            throw new \InvalidArgumentException('Missing fields for insert request.');
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

    private function serializeQueryOrPurge(): string
    {
        if ($this->consumerId === null || $this->resourceId === null) {
            throw new \InvalidArgumentException('Missing fields for query/purge request.');
        }

        $buffer = '';
        $buffer .= pack('C', $this->requestType->value); // request_type (1 byte)
        $buffer .= pack('C', strlen($this->consumerId)); // consumer_id_size (1 byte)
        $buffer .= pack('C', strlen($this->resourceId)); // resource_id_size (1 byte)
        $buffer .= $this->consumerId;                    // consumer_id (N bytes)
        $buffer .= $this->resourceId;                    // resource_id (M bytes)

        return $buffer;
    }

    private function serializeUpdate(): string
    {
        if ($this->attribute === null || $this->change === null || $this->value === null || $this->consumerId === null || $this->resourceId === null) {
            throw new \InvalidArgumentException('Missing fields for update request.');
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

    private function packUint64LE(int $value): string
    {
        $low = $value & 0xFFFFFFFF;
        $high = ($value >> 32) & 0xFFFFFFFF;
        return pack('V2', $low, $high);
    }

    public function toBytes(): string
    {
        return match ($this->requestType) {
            RequestType::INSERT => $this->serializeInsert(),
            RequestType::QUERY, RequestType::PURGE => $this->serializeQueryOrPurge(),
            RequestType::UPDATE => $this->serializeUpdate(),
        };
    }
}