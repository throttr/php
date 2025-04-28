<?php

namespace Throttr\SDK;

class PendingRequest
{
    private string $buffer;

    public function __construct(string $buffer)
    {
        $this->buffer = $buffer;
    }

    public function buffer(): string
    {
        return $this->buffer;
    }
}