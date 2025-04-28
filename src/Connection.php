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

use RuntimeException;
use SplQueue;
use Throttr\SDK\Exceptions\ConnectionException;
use Throttr\SDK\Exceptions\ProtocolException;

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
     * Constructor
     *
     * @param string $host
     * @param int    $port
     */
    public function __construct(string $host, int $port)
    {
        $address = "tcp://{$host}:{$port}";
        $this->socket = @stream_socket_client(
            $address,
            $errno,
            $errstr,
            5.0,
            STREAM_CLIENT_CONNECT
        );

        if (!$this->socket) {
            throw new RuntimeException("Failed to connect to {$address}: {$errstr} ({$errno})"); // @codeCoverageIgnore
        }

        stream_set_timeout($this->socket, 5);

        $this->queue = new SplQueue();
    }

    /**
     * Send request
     *
     * @param Request $request
     * @return Response
     */
    public function send(Request $request): Response
    {
        $buffer = $request->toBytes();
        $pending = new PendingRequest($buffer);

        $this->queue->enqueue($pending);

        return $this->processQueue();
    }

    /**
     * Process queue
     *
     * @return Response
     */
    private function processQueue(): Response
    {
        if ($this->busy || $this->queue->isEmpty()) {
            throw new ConnectionException('No request to process or connection is busy.'); // @codeCoverageIgnore
        }

        /** @var PendingRequest $pending */
        $pending = $this->queue->dequeue();

        $this->busy = true;

        try {
            $written = fwrite($this->socket, $pending->buffer());
            if ($written === false || $written !== strlen($pending->buffer())) {
                throw new ConnectionException('Failed to write complete data to socket.'); // @codeCoverageIgnore
            }

            $firstByteRequestType = ord($pending->buffer()[0]);

            $responseLength = match ($firstByteRequestType) {
                0x01, // Insert
                0x02  // Query
                => 18,
                0x03, // Update
                0x04  // Purge
                => 1,
                default => throw new ProtocolException('Unknown request type: ' . $firstByteRequestType), // @codeCoverageIgnore
            };

            $responseBytes = fread($this->socket, $responseLength);
            if ($responseBytes === false || strlen($responseBytes) !== $responseLength) {
                throw new ConnectionException('Failed to read full response payload.'); // @codeCoverageIgnore
            }

            return Response::fromBytes($responseBytes);
        } finally {
            $this->busy = false;
        }
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
