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

use Throttr\SDK\Enum\TTLType;
use Throttr\SDK\Enum\AttributeType;
use Throttr\SDK\Enum\ChangeType;
use Throttr\SDK\Enum\ValueSize;
use Throttr\SDK\Exceptions\ServiceException;
use Throttr\SDK\Requests\BaseRequest;
use Throttr\SDK\Requests\InsertRequest;
use Throttr\SDK\Requests\PurgeRequest;
use Throttr\SDK\Requests\QueryRequest;
use Throttr\SDK\Requests\UpdateRequest;

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
     * Value size
     *
     * @var ValueSize
     */
    private ValueSize $size;

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
     * @param ValueSize $size
     * @param int $maxConnections
     */
    public function __construct(string $host, int $port, ValueSize $size, int $maxConnections)
    {
        if ($maxConnections <= 0) {
            throw new \InvalidArgumentException('maxConnections must be greater than 0.'); // @codeCoverageIgnore
        }

        $this->host = $host;
        $this->port = $port;
        $this->size = $size;
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
            $connection = new Connection($this->host, $this->port, $this->size);
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
     * @param string $key
     * @param int $ttl
     * @param TTLType $ttlType
     * @param int $quota
     * @return Response
     */
    public function insert(string $key, int $ttl, TTLType $ttlType, int $quota): Response
    {
        $request = new InsertRequest(
            key: $key,
            quota: $quota,
            ttl_type: $ttlType,
            ttl: $ttl
        );

        return $this->send([$request])[0];
    }

    /**
     * Query
     *
     * @param string $key
     * @return Response
     */
    public function query(string $key): Response
    {
        $request = new QueryRequest(
            key: $key,
        );

        return $this->send([$request])[0];
    }

    /**
     * Purge
     *
     * @param string $key
     * @return Response
     */
    public function purge(string $key): Response
    {
        $request = new PurgeRequest(
            key: $key
        );

        return $this->send([$request])[0];
    }

    /**
     * Update
     *
     * @param string $key
     * @param AttributeType $attribute
     * @param ChangeType $change
     * @param int $value
     * @return Response
     */
    public function update(string $key, AttributeType $attribute, ChangeType $change, int $value): Response
    {
        $request = new UpdateRequest(
            attribute: $attribute,
            change: $change,
            value: $value,
            key: $key,
        );

        return $this->send([$request])[0];
    }

    /**
     * Send
     *
     * @param BaseRequest|array $requests
     * @return Response|array
     */
    public function send(BaseRequest|array $requests): Response|array
    {
        if (empty($this->connections)) {
            throw new ServiceException('No available connections.'); // @codeCoverageIgnore
        }

        $index = $this->roundRobinIndex;
        $this->roundRobinIndex = ($this->roundRobinIndex + 1) % count($this->connections);

        $connection = $this->connections[$index];

        return $connection->send($requests);
    }
}
