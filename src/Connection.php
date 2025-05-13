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

use SplQueue;
use Throttr\SDK\Enum\RequestType;
use Throttr\SDK\Enum\ValueSize;
use Throttr\SDK\Exceptions\ConnectionException;
use Throttr\SDK\Exceptions\ProtocolException;
use Throttr\SDK\Requests\BaseRequest;

/**
 * Connection
 */
class Connection
{
    /**
     * Socket
     *
     * @var resource|null
     */
    private $socket;

    /**
     * Queue
     *
     * @var SplQueue
     */
    private SplQueue $queue;

    /**
     * Busy
     *
     * @var bool
     */
    private bool $busy = false;

    /**
     * Value size
     *
     * @var ValueSize
     */
    private ValueSize $size;

    /**
     * Constructor
     *
     * @param string $host
     * @param int    $port
     * @param ValueSize    $size
     */
    public function __construct(string $host, int $port, ValueSize $size)
    {
        $this->size = $size;

        $address = "tcp://{$host}:{$port}";
        $this->socket = @stream_socket_client(
            $address,
            $errno,
            $errstr,
            5.0,
            STREAM_CLIENT_CONNECT
        );

        if (!$this->socket) {
            throw new ConnectionException("Failed to connect to {$address}: {$errstr} ({$errno})"); // @codeCoverageIgnore
        }

        stream_set_timeout($this->socket, 5);

        $rawSocket = @socket_import_stream($this->socket);
        @socket_set_option($rawSocket, SOL_TCP, TCP_NODELAY, 1);

        $this->queue = new SplQueue();
    }

    /**
     * Send request
     *
     * @param array $requests
     * @return array
     */
    public function send(array $requests): array
    {
        $buffer = '';
        $operations = [];

        /** @var BaseRequest $request */
        foreach ($requests as $request) {
            $buffer .= $request->toBytes($this->size);
            $operations[] = $request->type;
        }

        $pending = new PendingWrite($buffer, $operations);

        $this->queue->enqueue($pending);

        return $this->processQueue();
    }

    /**
     * Process queue
     *
     * @return array
     */
    private function processQueue(): array
    {
        if ($this->busy || $this->queue->isEmpty()) {
            throw new ConnectionException('No request to process or connection is busy.'); // @codeCoverageIgnore
        }

        /** @var PendingWrite $pending */
        $pending = $this->queue->dequeue();

        $this->busy = true;

        try {
            $written = fwrite($this->socket, $pending->buffer());
            if ($written === false || $written !== strlen($pending->buffer())) {
                throw new ConnectionException('Failed to write complete data to socket.'); // @codeCoverageIgnore
            }

            $responseBytes = fread($this->socket, count($pending->operations));
            if ($responseBytes === false) {
                throw new ConnectionException('Failed to read response payload.'); // @codeCoverageIgnore
            }

            return $this->processResponses($pending, $responseBytes);
        } finally {
            $this->busy = false;
        }
    }

    /**
     * Process responses
     *
     * @param PendingWrite $pending
     * @param string $responseBytes
     * @return array
     */
    private function processResponses(PendingWrite $pending, string $responseBytes): array
    {
        $responses = [];
        $offset = 0;

        foreach ($pending->operations as $operation) {
            $responses[] = match ($operation) {
                RequestType::INSERT, RequestType::UPDATE, RequestType::PURGE, RequestType::SET => $this->handleStatusResponse($responseBytes, $offset, $operation),
                RequestType::QUERY, RequestType::GET => $this->handlePayloadResponse($responseBytes, $offset, $operation),
                default => throw new ProtocolException("Unknown operation type: $operation->value"), // @codeCoverageIgnore
            };

            $offset++;
        }

        return $responses;
    }

    /**
     * Handle simple response
     *
     * @param string $responseBytes
     * @param int $offset
     * @param RequestType $operation
     * @return Response
     */
    private function handleStatusResponse(string $responseBytes, int $offset, RequestType $operation): Response
    {
        return Response::fromBytes($responseBytes[$offset], $this->size, $operation);
    }

    /**
     * Handle full response
     *
     * @param string $responseBytes
     * @param int $offset
     * @param RequestType $operation
     * @return Response
     */
    private function handlePayloadResponse(string &$responseBytes, int &$offset, RequestType $operation): Response
    {
        if (ord($responseBytes[$offset]) === 0x00) {
            return Response::fromBytes($responseBytes[$offset], $this->size, $operation);
        }

        $pendingBufferLength = $this->size->value * 2 + 1;

        $scopeBytes = fread($this->socket, $pendingBufferLength);

        if ($scopeBytes === false) {
            throw new ConnectionException('Failed to read full response payload.'); // @codeCoverageIgnore
        }

        $responseBytes .= $scopeBytes;

        if (RequestType::GET->value === $operation->value) {
            $valueBufferLength = unpack(BaseRequest::pack($this->size), substr($responseBytes, strlen($responseBytes) - $this->size->value, $this->size->value))[1];
            $valueBytes = fread($this->socket, $valueBufferLength);

            if ($valueBytes === false) {
                throw new ConnectionException('Failed to read full response payload.'); // @codeCoverageIgnore
            }

            $responseBytes .= $valueBytes;

            $pendingBufferLength += $valueBufferLength;
        }

        $response = Response::fromBytes(
            substr($responseBytes, $offset, $pendingBufferLength + 1),
            $this->size,
            $operation
        );

        $offset += $pendingBufferLength;
        return $response;
    }

    /**
     * Close connection
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }
}
