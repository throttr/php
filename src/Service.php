<?php

declare(strict_types=1);

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
use Throttr\SDK\Requests\ChannelRequest;
use Throttr\SDK\Requests\ChannelsRequest;
use Throttr\SDK\Requests\ConnectionRequest;
use Throttr\SDK\Requests\ConnectionsRequest;
use Throttr\SDK\Requests\GetRequest;
use Throttr\SDK\Requests\InfoRequest;
use Throttr\SDK\Requests\InsertRequest;
use Throttr\SDK\Requests\ListRequest;
use Throttr\SDK\Requests\PurgeRequest;
use Throttr\SDK\Requests\QueryRequest;
use Throttr\SDK\Requests\SetRequest;
use Throttr\SDK\Requests\StatRequest;
use Throttr\SDK\Requests\StatsRequest;
use Throttr\SDK\Requests\UpdateRequest;
use Throttr\SDK\Requests\WhoamiRequest;
use Throttr\SDK\Responses\ChannelResponse;
use Throttr\SDK\Responses\ChannelsResponse;
use Throttr\SDK\Responses\ConnectionResponse;
use Throttr\SDK\Responses\ConnectionsResponse;
use Throttr\SDK\Responses\GetResponse;
use Throttr\SDK\Responses\InfoResponse;
use Throttr\SDK\Responses\IResponse;
use Throttr\SDK\Responses\ListResponse;
use Throttr\SDK\Responses\QueryResponse;
use Throttr\SDK\Responses\StatResponse;
use Throttr\SDK\Responses\StatsResponse;
use Throttr\SDK\Responses\StatusResponse;
use Throttr\SDK\Responses\WhoamiResponse;

/**
 * Service
 */
final class Service
{
    /**
     * Connections
     *
     * @var array
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
    public ValueSize $size;

    /**
     * Max connections
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
     * INSERT
     *
     * @param string $key
     * @param int $ttl
     * @param TTLType $ttlType
     * @param int $quota
     * @return StatusResponse
     */
    public function insert(string $key, int $ttl, TTLType $ttlType, int $quota): StatusResponse
    {
        $request = new InsertRequest($key, $quota, $ttlType, $ttl);
        return $this->send([$request])[0];
    }

    /**
     * LIST
     *
     * @return ListResponse
     */
    public function list(): ListResponse
    {
        $request = new ListRequest();
        return $this->send([$request])[0];
    }

    /**
     * STATS
     *
     * @return StatsResponse
     */
    public function stats(): StatsResponse
    {
        $request = new StatsRequest();
        return $this->send([$request])[0];
    }

    /**
     * INFO
     *
     * @return InfoResponse
     */
    public function info(): InfoResponse
    {
        $request = new InfoRequest();
        return $this->send([$request])[0];
    }

    /**
     * QUERY
     *
     * @param string $key
     * @return QueryResponse
     */
    public function query(string $key): QueryResponse
    {
        $request = new QueryRequest($key);
        return $this->send([$request])[0];
    }

    /**
     * STAT
     *
     * @param string $key
     * @return StatResponse
     */
    public function stat(string $key): StatResponse
    {
        $request = new StatRequest($key);
        return $this->send([$request])[0];
    }

    /**
     * PURGE
     *
     * @param string $key
     * @return StatusResponse
     */
    public function purge(string $key): StatusResponse
    {
        $request = new PurgeRequest($key);
        return $this->send([$request])[0];
    }

    /**
     * UPDATE
     *
     * @param string $key
     * @param AttributeType $attribute
     * @param ChangeType $change
     * @param int $value
     * @return StatusResponse
     */
    public function update(string $key, AttributeType $attribute, ChangeType $change, int $value): StatusResponse
    {
        $request = new UpdateRequest($attribute, $change, $value, $key);
        return $this->send([$request])[0];
    }

    /**
     * SET
     *
     * @param string $key
     * @param int $ttl
     * @param TTLType $ttlType
     * @param string $value
     * @return StatusResponse
     */
    public function set(string $key, int $ttl, TTLType $ttlType, string $value): StatusResponse
    {
        $request = new SetRequest($key, $ttlType, $ttl, $value);
        return $this->send([$request])[0];
    }

    /**
     * GET
     *
     * @param string $key
     * @return GetResponse
     */
    public function get(string $key): GetResponse
    {
        $request = new GetRequest($key);
        return $this->send([$request])[0];
    }

    /**
     * CONNECTIONS
     *
     * @return mixed
     */
    public function connections(): ConnectionsResponse
    {
        $request = new ConnectionsRequest();
        return $this->send($request)[0];
    }

    /**
     * CONNECTION
     *
     * @param string $id
     * @return mixed
     */
    public function connection(string $id): ConnectionResponse
    {
        $request = new ConnectionRequest($id);
        return $this->send($request)[0];
    }

    /**
     * WHOAMI
     *
     * @return mixed
     */
    public function whoami(): WhoamiResponse
    {
        $request = new WhoamiRequest();
        return $this->send($request)[0];
    }

    /**
     * CHANNELS
     *
     * @return mixed
     */
    public function channels(): ChannelsResponse
    {
        $request = new ChannelsRequest();
        return $this->send($request)[0];
    }

    /**
     * CHANNEL
     *
     * @param string $name
     * @return mixed
     */
    public function channel(string $name): ChannelResponse
    {
        $request = new ChannelRequest($name);
        return $this->send($request)[0];
    }

    /**
     * Send
     *
     * @param BaseRequest|array $requests
     * @return IResponse|array
     */
    public function send(BaseRequest|array $requests): IResponse|array
    {
        $index = $this->roundRobinIndex;
        $this->roundRobinIndex = ($this->roundRobinIndex + 1) % count($this->connections);

        /** @var Connection $connection */
        $connection = $this->connections[$index];

        return $connection->send(is_array($requests) ? $requests : [$requests]);
    }
}
