<?php

namespace Throttr\SDK\Responses;

use Throttr\SDK\Enum\KeyType;
use Throttr\SDK\Enum\TTLType;
use Throttr\SDK\Enum\ValueSize;
use Throttr\SDK\Requests\BaseRequest;

/**
 * InfoResponse
 */
class InfoResponse extends Response implements IResponse {
    /**
     * Constructor
     *
     * @param string $data
     * @param bool $status
     * @param array $attributes
     */
    public function __construct(public string $data, public bool $status, public array $attributes) {}

    /**
     * From bytes
     *
     * @param string $data
     * @param ValueSize $size
     * @return InfoResponse|null
     */
    public static function fromBytes(string $data, ValueSize $size) : InfoResponse|null {
        $valueSize = $size->value;
        $offset = 0;

        if (strlen($data) < 433) return null;

        $status = ord($data[$offset]) === 1;
        $offset++;

        if ($status) {
            $now = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += 8;


            $total_requests = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += 8;


            $total_requests_per_minute = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += 8;

            $types = [
                'INSERT' => ["total" => 0, "per_minute" => 0],
                'QUERY' => ["total" => 0, "per_minute" => 0],
                'UPDATE' => ["total" => 0, "per_minute" => 0],
                'PURGE' => ["total" => 0, "per_minute" => 0],
                'GET' => ["total" => 0, "per_minute" => 0],
                'SET' => ["total" => 0, "per_minute" => 0],
                'LIST' => ["total" => 0, "per_minute" => 0],
                'INFO' => ["total" => 0, "per_minute" => 0],
                'STATS' => ["total" => 0, "per_minute" => 0],
                'STAT' => ["total" => 0, "per_minute" => 0],
                'SUBSCRIBE' => ["total" => 0, "per_minute" => 0],
                'UNSUBSCRIBE' => ["total" => 0, "per_minute" => 0],
                'PUBLISH' => ["total" => 0, "per_minute" => 0],
                'CHANNEL' => ["total" => 0, "per_minute" => 0],
                'CHANNELS' => ["total" => 0, "per_minute" => 0],
                'WHOAMI' => ["total" => 0, "per_minute" => 0],
                'CONNECTION' => ["total" => 0, "per_minute" => 0],
                'CONNECTIONS' => ["total" => 0, "per_minute" => 0],
            ];

            foreach ($types as $key => $type) {
                $types[$key]["total"] = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
                $offset += 8;
                $types[$key]["per_minute"] = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
                $offset += 8;
            }

            $total_read = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += 8;

            $total_read_per_minute = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += 8;

            $total_write = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += 8;

            $total_write_per_minute = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += 8;

            $total_keys = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += 8;

            $total_counters = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += 8;

            $total_buffers = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += 8;

            $total_allocated_bytes_on_counters = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += 8;

            $total_allocated_bytes_on_buffers = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += 8;

            $total_subscriptions = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += 8;

            $total_channels = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += 8;

            $started_at = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += 8;

            $total_connections = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += 8;

            $version = substr($data, $offset, 16);

            return new InfoResponse($data, true, [
               "now" => $now,
               "total_requests" => $total_requests,
               "total_requests_per_minute" => $total_requests_per_minute,
               "requests" => $types,
               "total_read" => $total_read,
               "total_read_per_minute" => $total_read_per_minute,
               "total_write" => $total_write,
               "total_write_per_minute" => $total_write_per_minute,
               "total_keys" => $total_keys,
               "total_counters" => $total_counters,
               "total_buffers" => $total_buffers,
               "total_allocated_bytes_on_counters" => $total_allocated_bytes_on_counters,
               "total_allocated_bytes_on_buffers" => $total_allocated_bytes_on_buffers,
               "total_subscriptions" => $total_subscriptions,
               "total_channels" => $total_channels,
               "started_at" => $started_at,
               "total_connections" => $total_connections,
                "version" => $version,
            ]);
        }

        return new InfoResponse($data, false, []);
    }
}

