<?php declare(strict_types=1);

namespace Throttr\SDK;

use Throttr\SDK\Enum\RequestType;
use Throttr\SDK\Enum\TTLType;
use Throttr\SDK\Enum\AttributeType;
use Throttr\SDK\Enum\ChangeType;
use RuntimeException;

final class Service
{
    /**
     * @var Connection[]
     */
    private array $connections = [];

    private int $roundRobinIndex = 0;
    private string $host;
    private int $port;
    private int $maxConnections;

    public function __construct(string $host, int $port, int $maxConnections)
    {
        if ($maxConnections <= 0) {
            throw new \InvalidArgumentException('maxConnections must be greater than 0.');
        }

        $this->host = $host;
        $this->port = $port;
        $this->maxConnections = $maxConnections;
    }

    public function connect(): void
    {
        for ($i = 0; $i < $this->maxConnections; $i++) {
            $connection = new Connection($this->host, $this->port);
            $this->connections[] = $connection;
        }
    }

    public function close(): void
    {
        foreach ($this->connections as $connection) {
            $connection->close();
        }

        $this->connections = [];
    }

    public function insert(string $consumerId, string $resourceId, int $ttl, TTLType $ttlType, int $quota, int $usage = 0): Response
    {
        $request = new Request(
            requestType: RequestType::INSERT,
            quota: $quota,
            usage: $usage,
            ttlType: $ttlType,
            ttl: $ttl,
            consumerId: $consumerId,
            resourceId: $resourceId
        );

        return $this->send($request);
    }

    public function query(string $consumerId, string $resourceId): Response
    {
        $request = new Request(
            requestType: RequestType::QUERY,
            consumerId: $consumerId,
            resourceId: $resourceId
        );

        return $this->send($request);
    }

    public function purge(string $consumerId, string $resourceId): Response
    {
        $request = new Request(
            requestType: RequestType::PURGE,
            consumerId: $consumerId,
            resourceId: $resourceId
        );

        return $this->send($request);
    }

    public function update(string $consumerId, string $resourceId, AttributeType $attribute, ChangeType $change, int $value): Response
    {
        $request = new Request(
            requestType: RequestType::UPDATE,
            attribute: $attribute,
            change: $change,
            value: $value,
            consumerId: $consumerId,
            resourceId: $resourceId
        );

        return $this->send($request);
    }

    private function send(Request $request): Response
    {
        if (empty($this->connections)) {
            throw new RuntimeException('No available connections.');
        }

        $index = $this->roundRobinIndex;
        $this->roundRobinIndex = ($this->roundRobinIndex + 1) % count($this->connections);

        $connection = $this->connections[$index];

        return $connection->send($request);
    }
}