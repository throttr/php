<?php

namespace Throttr\SDK\Responses;

use Throttr\SDK\Enum\KeyType;
use Throttr\SDK\Enum\TTLType;
use Throttr\SDK\Enum\ValueSize;
use Throttr\SDK\Requests\BaseRequest;

/**
 * ListResponse
 */
class ListResponse extends Response implements IResponse {
    /**
     * Constructor
     *
     * @param string $data
     * @param bool $status
     * @param array $keys
     */
    public function __construct(public string $data, public bool $status, public array $keys) {}

    /**
     * From bytes
     *
     * @param string $data
     * @param ValueSize $size
     * @return ListResponse|null
     */
    public static function fromBytes(string $data, ValueSize $size) : ListResponse|null {
        $valueSize = $size->value;
        $offset = 0;

        // Less than 1 byte? not enough for status.
        if (strlen($data) < 1) return null;

        $status = ord($data[$offset]) === 1;
        $offset++;

        if ($status) {
            // Less than 1 + N bytes? not enough for quota.
            if (strlen($data) < 1 + 8) return null;

            $fragments = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
            $offset += ValueSize::UINT64->value;

            if ($fragments === 0) return new ListResponse($data, true, []);

            $keys_container = [];

            for ($i = 0; $i < $fragments; ++$i) {
                // Less than offset + 8 bytes? not enough for fragment index.
                if (strlen($data) < $offset + ValueSize::UINT64->value) return null;

                $fragment = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
                $offset += ValueSize::UINT64->value;

                // Less than offset + 8 bytes? not enough for fragment keys count.
                if (strlen($data) < $offset + ValueSize::UINT64->value) return null;

                $number_of_keys = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
                $offset += ValueSize::UINT64->value;

                $keys_in_fragment = [];

                // Per key in fragment
                for ($e = 0; $e < $number_of_keys; ++$e) {
                    // Less than offset + 1 byte? not enough for key size.
                    if (strlen($data) < $offset + ValueSize::UINT8->value) return null;

                    $key_size = unpack(BaseRequest::pack(ValueSize::UINT8), substr($data, $offset, ValueSize::UINT8->value))[1];
                    $offset += ValueSize::UINT8->value;

                    // Less than offset + 1 byte? not enough for key type.
                    if (strlen($data) < $offset + ValueSize::UINT8->value) return null;

                    $key_type = KeyType::from(unpack(BaseRequest::pack(ValueSize::UINT8), substr($data, $offset, ValueSize::UINT8->value))[1]);
                    $offset += ValueSize::UINT8->value;

                    // Less than offset + 1 byte? not enough for ttl type.
                    if (strlen($data) < $offset + ValueSize::UINT8->value) return null;

                    $ttl_type = TTLType::from(unpack(BaseRequest::pack(ValueSize::UINT8), substr($data, $offset, ValueSize::UINT8->value))[1]);
                    $offset += ValueSize::UINT8->value;

                    // Less than offset + 8 bytes? not enough for ttl.
                    if (strlen($data) < $offset + ValueSize::UINT64->value) return null;

                    $ttl = unpack(BaseRequest::pack(ValueSize::UINT64), substr($data, $offset, ValueSize::UINT64->value))[1];
                    $offset += ValueSize::UINT64->value;

                    // Less than offset + N bytes? not enough for bytes used.
                    if (strlen($data) < $offset + $size->value) return null;

                    $bytes_used = unpack(BaseRequest::pack( $size), substr($data, $offset,  $size->value))[1];
                    $offset += $size->value;

                    $keys_in_fragment[] = [
                        "size" => $key_size,
                        "type" => $key_type,
                        "ttl_type" => $ttl_type,
                        "ttl" => $ttl,
                        "bytes_used" => $bytes_used,
                    ];
                }

                $total = array_sum(array_column($keys_in_fragment, 'size'));

                // Less than offset + total keys bytes? not enough for name parsing
                if (strlen($data) < $offset + $total) return null;

                for ($e = 0; $e < $number_of_keys; ++$e) {
                    $keys_in_fragment[$e]["key"] = substr($data, $offset, $keys_in_fragment[$e]["size"]);
                    $offset += $keys_in_fragment[$e]["size"];
                    unset($keys_in_fragment[$e]["size"]);
                }

                $keys_container = array_merge($keys_container, $keys_in_fragment);
            }

            return new ListResponse($data, true, $keys_container);
        }

        return new ListResponse($data, false, []);
    }
}

