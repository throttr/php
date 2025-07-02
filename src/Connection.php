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

use Swoole\Coroutine\Client;
use Swoole\Coroutine\Channel;
use Throttr\SDK\Enum\RequestType;
use Throttr\SDK\Enum\ValueSize;
use Throttr\SDK\Exceptions\ConnectionException;
use Throttr\SDK\Exceptions\ProtocolException;
use Throttr\SDK\Requests\BaseRequest;

/**
 * Connection (Swoole async, con centralización de lectura segura)
 */
class Connection
{
    private Client $client;
    private ValueSize $size;
    private Channel $queue;
    private \SplQueue $pendingChannels;
    private bool $connected = false;

    public function __construct(string $host, int $port, ValueSize $size)
    {
        $this->size = $size;
        $this->client = new Client(SWOOLE_SOCK_TCP);

        if (!$this->client->connect($host, $port, 5.0)) {
            throw new ConnectionException("Failed to connect to {$host}:{$port} ({$this->client->errCode})");
        }

        $this->connected = true;
        $this->queue = new Channel(1024);
        $this->pendingChannels = new \SplQueue();
        go(fn() => $this->processQueue());
        go(fn() => $this->processResponses());
    }

    public function send(array $requests): Channel
    {
        $buffer = '';
        $operations = [];

        foreach ($requests as $request) {
            $buffer .= $request->toBytes($this->size);
            $operations[] = $request->type;
        }

        $chan = new Channel(1);
        $this->queue->push([$buffer, $operations, $chan]);
        return $chan;
    }

    private function processQueue(): void
    {
//        while (true) {
//            $job = $this->queue->pop();
//            if ($job === false) break;
//
//            [$buffer, $operations, $chan] = $job;

//            try {
//                $written = $this->client->send($buffer);
//                if ($written === false || $written !== strlen($buffer)) {
//                    throw new ConnectionException("Failed to write complete data to socket.");
//                }
//
//                $this->pendingChannels->push([$operations, $chan]);
//            } catch (\Throwable $e) {
//                $chan->push($e);
//            }
//        }
    }

    private function processResponses(): void
    {
        while (true) {
            if ($this->pendingChannels->isEmpty()) {
                echo "WTF";
                break;
            }

            [$operations, $chan] = $this->pendingChannels->shift();
            $responses = [];

            try {
                foreach ($operations as $operation) {
                    $status = $this->recvExact(1);

                    $responses[] = match ($operation) {
                        RequestType::INSERT, RequestType::UPDATE, RequestType::PURGE, RequestType::SET =>
                        $this->handleStatusResponse($status, $operation),

                        RequestType::QUERY, RequestType::GET =>
                        $this->handlePayloadResponse($status, $operation),

                        default => throw new ProtocolException("Unknown operation type: {$operation->value}"),
                    };
                }

                $chan->push($responses);
            } catch (\Throwable $e) {
                $chan->push($e);
            }
        }
    }

    private function handleStatusResponse(string $status, RequestType $operation): Response
    {
        return Response::fromBytes($status, $this->size, $operation);
    }

    private function handlePayloadResponse(string $status, RequestType $operation): Response
    {
        $payload = $status;

        $scopeLength = $this->size->value * 2;
        $scope = $this->recvExact($scopeLength);
        $payload .= $scope;

        if ($operation === RequestType::GET) {
            $valueLength = unpack(BaseRequest::pack($this->size), substr($scope, -$this->size->value))[1];
            $value = $this->recvExact($valueLength);
            $payload .= $value;
        }

        return Response::fromBytes($payload, $this->size, $operation);
    }

    private function recvExact(int $length): string
    {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = $this->client->recv();
            $data .= $chunk;
        }
        return $data;
    }

    public function close(): void
    {
        if ($this->connected) {
            $this->client->close();
            $this->connected = false;
        }
    }
}
