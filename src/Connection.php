<?php

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

use Co;
use SplQueue;
use Swoole\Coroutine\Client;
use Swoole\Coroutine\Channel;
use Throttr\SDK\Enum\RequestType;
use Throttr\SDK\Enum\ValueSize;

/**
 * Connection (Swoole async, con centralización de lectura segura)
 */
class Connection
{
    /**
     * Client
     *
     * @var Client
     */
    private Client $client;

    /**
     * Value size
     *
     * @var ValueSize
     */
    private ValueSize $size;

    /**
     * Queue
     *
     * @var Channel
     */
    private Channel $queue;

    /**
     * Pending channels
     *
     * @var Channel
     */
    private Channel $pendingChannels;

    /**
     * Connected
     * @var bool
     */
    private bool $connected;

    /**
     * Tasks
     *
     * @var array
     */
    private array $tasks;

    /**
     * Constructor
     *
     * @param string $host
     * @param int $port
     * @param ValueSize $size
     */
    public function __construct(string $host, int $port, ValueSize $size)
    {
        $this->size = $size;
        $this->client = new Client(SWOOLE_SOCK_TCP);

        $this->client->connect($host, $port, 5.0);

        $this->connected = true;
        $this->queue = new Channel(1024);
        $this->pendingChannels = new Channel(1024);

        $this->tasks = [
            go(fn() => $this->processQueue()),
            go(fn() => $this->processResponses()),
        ];
    }

    /**
     * Send
     *
     * @param array $requests
     * @return array
     */
    public function send(array $requests): array
    {
        $buffer = '';
        $operations = [];

        foreach ($requests as $request) {
            $buffer .= $request->toBytes($this->size);
            $operations[] = $request->type;
        }

        $channel = new Channel(1);
        $this->queue->push([$buffer, $operations, $channel]);
        $result = $channel->pop();
        $channel->close();
        return $result;
    }

    /**
     * Process queue
     *
     * @return void
     */
    private function processQueue(): void
    {
        while ($this->connected) {
            $job = $this->queue->pop(3);

            if ($job === false) {
                break;
            }

            $this->client->send($job[0]);
            $this->pendingChannels->push([$job[1], $job[2]]);
        }
    }

    /**
     * Process responses
     *
     * @return void
     */
    private function processResponses(): void
    {
        while ($this->connected) {
            $result = $this->pendingChannels->pop(60);

            $responses = [];

            $data = $this->client->recv();

            foreach ($result[0] as $operation) {
                /* @var RequestType $operation */
                $responses[] = match ($operation) {
                    RequestType::INSERT, RequestType::UPDATE, RequestType::PURGE, RequestType::SET =>
                    $this->handleStatusResponse($data, $operation),

                    RequestType::QUERY, RequestType::GET =>
                    $this->handlePayloadResponse($data, $operation),
                };
            }

            $result[1]->push($responses);
        }
    }

    /**
     * Handle status response
     *
     * @param string $status
     * @param RequestType $operation
     * @return Response
     */
    private function handleStatusResponse(string $status, RequestType $operation): Response
    {
        return Response::fromBytes($status, $this->size, $operation);
    }

    /**
     * Handle payload response
     *
     * @param string $status
     * @param RequestType $operation
     * @return Response
     */
    private function handlePayloadResponse(string $status, RequestType $operation): Response
    {
        $payload = $status;

        return Response::fromBytes($payload, $this->size, $operation);
    }

    /**
     * Close
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->connected) {
            $this->connected = false;
            $this->client->close();
            $this->queue->close();
            $this->pendingChannels->close();
            Co::join($this->tasks);
        }
    }
}
