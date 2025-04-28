<?php declare(strict_types=1);

namespace Throttr\SDK;

final class Response
{
    private ?bool $can;
    private ?bool $success;
    private ?int $quotaRemaining;
    private ?int $ttlRemaining;
    private ?int $ttlType;

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
            throw new \InvalidArgumentException('Invalid response length: ' . $length);
        }
    }

    public function can(): ?bool
    {
        return $this->can;
    }

    public function success(): ?bool
    {
        return $this->success;
    }

    public function quotaRemaining(): ?int
    {
        return $this->quotaRemaining;
    }

    public function ttlRemaining(): ?int
    {
        return $this->ttlRemaining;
    }

    public function ttlType(): ?int
    {
        return $this->ttlType;
    }

    public function ttlRemainingSeconds(): ?float
    {
        if ($this->ttlRemaining === null || $this->ttlType === null) {
            return null;
        }

        return match ($this->ttlType) {
            0 => $this->ttlRemaining / 1_000_000_000,
            1 => $this->ttlRemaining / 1_000,
            2 => (float)$this->ttlRemaining,
            default => (float)$this->ttlRemaining,
        };
    }

    private static function unpackUint64LE(string $data): int
    {
        [$low, $high] = array_values(unpack('V2', $data));
        return ($high << 32) | $low;
    }

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