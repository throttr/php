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

use Swoole\Coroutine;
use Throttr\SDK\Enum\TTLType;
use Throttr\SDK\Enum\AttributeType;
use Throttr\SDK\Enum\ChangeType;
use Throttr\SDK\Enum\ValueSize;
use Throttr\SDK\Exceptions\ServiceException;
use Throttr\SDK\Requests\BaseRequest;
use Throttr\SDK\Requests\GetRequest;
use Throttr\SDK\Requests\InsertRequest;
use Throttr\SDK\Requests\PurgeRequest;
use Throttr\SDK\Requests\QueryRequest;
use Throttr\SDK\Requests\SetRequest;
use Throttr\SDK\Requests\UpdateRequest;

/**
 * Service
 */
final class Service
{
    private array $connections = [];
    private int $roundRobinIndex = 0;
    private string $host;
    private int $port;
    private ValueSize $size;
    private int $maxConnections;

    public function __construct(string $host, int $port, ValueSize $size, int $maxConnections)
    {
        if ($maxConnections <= 0) {
            throw new \InvalidArgumentException('maxConnections must be greater than 0.');
        }

        $this->host = $host;
        $this->port = $port;
        $this->size = $size;
        $this->maxConnections = $maxConnections;
    }

    public function connect(): void
    {
        for ($i = 0; $i < $this->maxConnections; $i++) {
            $connection = new Connection($this->host, $this->port, $this->size);
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

    public function insert(string $key, int $ttl, TTLType $ttlType, int $quota): Response
    {
        $request = new InsertRequest($key, $quota, $ttlType, $ttl);
        return $this->send([$request])[0];
    }

    public function query(string $key): Response
    {
        $request = new QueryRequest($key);
        return $this->send([$request])[0];
    }

    public function purge(string $key): Response
    {
        $request = new PurgeRequest($key);
        return $this->send([$request])[0];
    }

    public function update(string $key, AttributeType $attribute, ChangeType $change, int $value): Response
    {
        $request = new UpdateRequest($attribute, $change, $value, $key);
        return $this->send([$request])[0];
    }

    public function set(string $key, int $ttl, TTLType $ttlType, string $value): Response
    {
        $request = new SetRequest($key, $ttlType, $ttl, $value);
        return $this->send([$request])[0];
    }

    public function get(string $key): Response
    {
        $request = new GetRequest($key);
        return $this->send([$request])[0];
    }

    public function send(BaseRequest|array $requests): Response|array
    {
        if (empty($this->connections)) {
            throw new ServiceException('No available connections.');
        }

        $index = $this->roundRobinIndex;
        $this->roundRobinIndex = ($this->roundRobinIndex + 1) % count($this->connections);

        $connection = $this->connections[$index];

        $chan = $connection->send(is_array($requests) ? $requests : [$requests]);
        $res = $chan->pop();

        if ($res instanceof \Throwable) {
            throw $res;
        }

        return $res;
    }
}
