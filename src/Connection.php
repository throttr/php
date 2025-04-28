<?php

namespace Throttr\SDK;

use RuntimeException;
use SplQueue;

class Connection
{
    /**
     * @var resource|null
     */
    private $socket;

    /**
     * @var SplQueue
     */
    private SplQueue $queue;

    /**
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
            5.0, // Timeout en segundos
            STREAM_CLIENT_CONNECT
        );

        if (!$this->socket) {
            throw new RuntimeException("Failed to connect to {$address}: {$errstr} ({$errno})");
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
            throw new RuntimeException('No request to process or connection is busy.');
        }

        /** @var PendingRequest $pending */
        $pending = $this->queue->dequeue();

        $this->busy = true;

        try {
            $written = fwrite($this->socket, $pending->buffer());
            if ($written === false || $written !== strlen($pending->buffer())) {
                throw new RuntimeException('Failed to write complete data to socket.');
            }

            $firstByteRequestType = ord($pending->buffer()[0]);

            $responseLength = match ($firstByteRequestType) {
                0x01, // Insert
                0x02  // Query
                => 18,
                0x03, // Update
                0x04  // Purge
                => 1,
                default => throw new RuntimeException('Unknown request type: ' . $firstByteRequestType),
            };

            $responseBytes = fread($this->socket, $responseLength);
            if ($responseBytes === false || strlen($responseBytes) !== $responseLength) {
                throw new RuntimeException('Failed to read full response payload.');
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