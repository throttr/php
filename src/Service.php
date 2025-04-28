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
use RuntimeException;

/**
 * Service
 */
final class Service
{
    /**
     * @var Connection[]
     */
    private array $connections = [];

    /**
     * Round-robin index
     *
     * @var int
     */
    private int $roundRobinIndex = 0;

    /**
     * Host
     *
     * @var string
     */
    private string $host;

    /**
     * Port
     *
     * @var int
     */
    private int $port;

    /**
     * Maximum connections
     *
     * @var int
     */
    private int $maxConnections;

    /**
     * Constructor
     *
     * @param string $host
     * @param int $port
     * @param int $maxConnections
     */
    public function __construct(string $host, int $port, int $maxConnections)
    {
        if ($maxConnections <= 0) {
            throw new \InvalidArgumentException('maxConnections must be greater than 0.'); // @codeCoverageIgnore
        }

        $this->host = $host;
        $this->port = $port;
        $this->maxConnections = $maxConnections;
    }

    /**
     * Connect
     *
     * @return void
     */
    public function connect(): void
    {
        for ($i = 0; $i < $this->maxConnections; $i++) {
            $connection = new Connection($this->host, $this->port);
            $this->connections[] = $connection;
        }
    }

    /**
     * Close
     *
     * @return void
     */
    public function close(): void
    {
        foreach ($this->connections as $connection) {
            $connection->close();
        }

        $this->connections = [];
    }

    /**
     * Insert
     *
     * @param string $consumerId
     * @param string $resourceId
     * @param int $ttl
     * @param TTLType $ttlType
     * @param int $quota
     * @param int $usage
     * @return Response
     */
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

    /**
     * Query
     *
     * @param string $consumerId
     * @param string $resourceId
     * @return Response
     */
    public function query(string $consumerId, string $resourceId): Response
    {
        $request = new Request(
            requestType: RequestType::QUERY,
            consumerId: $consumerId,
            resourceId: $resourceId
        );

        return $this->send($request);
    }

    /**
     * Purge
     *
     * @param string $consumerId
     * @param string $resourceId
     * @return Response
     */
    public function purge(string $consumerId, string $resourceId): Response
    {
        $request = new Request(
            requestType: RequestType::PURGE,
            consumerId: $consumerId,
            resourceId: $resourceId
        );

        return $this->send($request);
    }

    /**
     * Update
     *
     * @param string $consumerId
     * @param string $resourceId
     * @param AttributeType $attribute
     * @param ChangeType $change
     * @param int $value
     * @return Response
     */
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

    /**
     * Send
     *
     * @param Request $request
     * @return Response
     */
    private function send(Request $request): Response
    {
        if (empty($this->connections)) {
            throw new RuntimeException('No available connections.'); // @codeCoverageIgnore
        }

        $index = $this->roundRobinIndex;
        $this->roundRobinIndex = ($this->roundRobinIndex + 1) % count($this->connections);

        $connection = $this->connections[$index];

        return $connection->send($request);
    }
}