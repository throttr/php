<?php

namespace Throttr\SDK\Contracts;

use Throttr\SDK\Enum\ValueSize;

interface ShouldDefineSerialization
{
    public function toBytes(ValueSize $size) : string;
}