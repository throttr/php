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
use Throttr\SDK\Exceptions\ConnectionException;
use Throttr\SDK\Exceptions\ProtocolException;
use Throttr\SDK\Requests\BaseRequest;
use Throwable;

/**
 * Connection (Swoole async, con centralización de lectura segura)
 */
class Connection
{
    private Client $client;
    private ValueSize $size;
    private Channel $queue;
    private Channel $pendingChannels;
    private bool $connected = false;

    private array $tasks = [];

    public function __construct(string $host, int $port, ValueSize $size)
    {
        $this->size = $size;
        $this->client = new Client(SWOOLE_SOCK_TCP);

        if (!$this->client->connect($host, $port, 5.0)) {
            throw new ConnectionException("Failed to connect to {$host}:{$port} ({$this->client->errCode})");
        }

        $this->connected = true;
        $this->queue = new Channel(1024);
        $this->pendingChannels = new Channel(1024);

        $this->tasks = [
            go(fn() => $this->processQueue()),
            go(fn() => $this->processResponses()),
        ];
    }

    public function send(array $requests): array
    {
        echo "SENDING \n";
        $buffer = '';
        $operations = [];

        foreach ($requests as $request) {
            $buffer .= $request->toBytes($this->size);
            $operations[] = $request->type;
        }

        echo "CHANNEL CREATED \n";
        $channel = new Channel(1);
        $this->queue->push([$buffer, $operations, $channel]);
        echo "CHANNEL PUSHED WAITING FOR RESPONSE \n";
        $result = $channel->pop();
        $exitChannel = $channel->close();
        echo "CHANNEL EXIT: {$exitChannel}\n";
        return $result;
    }

    private function processQueue(): void
    {
        while (!$this->client->connected) {
            echo "NOT CONNECTED YET (processQueue) \n";
            Co::sleep(1);
        }

        while ($this->connected) {
            echo "JOB RETRIEVING \n";
            $job = $this->queue->pop(3);

            if ($job === false) {
                echo "CLIENT QUEUE CLOSED: {$this->client->errCode}::{$this->client->errMsg} \n";
                echo "QUEUE CLOSED\n";
                break;
            }

            try {
                $written = $this->client->send($job[0]);
                if ($written === false || $written !== strlen($job[0])) {
                    throw new ConnectionException("Failed to write complete data to socket.");
                }
                $this->pendingChannels->push([$job[1], $job[2]]);
            } catch (Throwable $e) {
                echo "SOMETHING WENT WRONG {$e->getMessage()} {$e->getFile()}::{$e->getLine()} \n";
                $job[2]->push($e);
            }
        }

        echo "COROUTINE QUEUE CLOSED\n";
    }

    private function processResponses(): void
    {
        while (!$this->client->connected) {
            echo "NOT CONNECTED YET (processResponses) \n";
            Co::sleep(1);
        }

        while ($this->connected) {
            echo "PENDING CHANNELS SHIFTING \n";
            $result = $this->pendingChannels->pop(3);
            echo "CHANNEL SHIFT WITH RESPONSE \n";

            if ($result === false) {
                echo "PENDING CHANNELS CLOSED\n";
                echo "PENDING CHANNELS CLOSED: {$this->client->errCode}::{$this->client->errMsg} \n";
                break;
            }

            $responses = [];

            try {
                $data = $this->client->recv();
                echo "RECEIVED: " . bin2hex($data) . "\n";

                foreach ($result[0] as $operation) {
                    /* @var RequestType $operation */
                    echo "OPERATION: " . $operation->value . "\n";
                    $responses[] = match ($operation) {
                        RequestType::INSERT, RequestType::UPDATE, RequestType::PURGE, RequestType::SET =>
                        $this->handleStatusResponse($data, $operation),

                        RequestType::QUERY, RequestType::GET =>
                        $this->handlePayloadResponse($data, $operation),

                        default => throw new ProtocolException("Unknown operation type: {$operation->value}"),
                    };
                }
                $encodes = [];
                foreach ($responses as $response) {
                    $encodes[] = json_encode($response->success());
                }

                echo "RESPONSES: " . json_encode($encodes) . "\n";

                $result[1]->push($responses);
            } catch (Throwable $e) {
                echo "SOMETHING WENT WRONG {$e->getMessage()} {$e->getFile()}::{$e->getLine()} \n";
                $result[1]->push($e);
            }
        }
        echo "COROUTINE RESPONSES CLOSED\n";
    }

    private function handleStatusResponse(string $status, RequestType $operation): Response
    {
        return Response::fromBytes($status, $this->size, $operation);
    }

    private function handlePayloadResponse(string $status, RequestType $operation): Response
    {
        $payload = $status;

//        if ($operation === RequestType::GET) {
//            $valueLength = unpack(BaseRequest::pack($this->size), substr($scope, -$this->size->value))[1];
//            $value = $this->recvExact($valueLength);
//            $payload .= $value;
//        }

        return Response::fromBytes($payload, $this->size, $operation);
    }

    public function close(): void
    {
        if ($this->connected) {
            echo "CLOSING ON CONNECTION \n";
            $this->connected = false;
            $exitCodeClient = $this->client->close();
            $exitCodeQueue = $this->queue->close();
            $exitCodePending = $this->pendingChannels->close();
            $exitCodeJoin = Co::join($this->tasks);
            echo "CLOSED ON CONNECTION \n";
            echo "EXITS: {$exitCodeJoin} {$exitCodePending} {$exitCodeQueue} {$exitCodeClient} \n";
            echo "WTF: {$this->pendingChannels->length()}\n";
            echo "WTF: {$this->queue->length()}\n";
        }
    }
}
