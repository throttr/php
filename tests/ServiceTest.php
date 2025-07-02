<?php declare(strict_types=1);

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

use Swoole\Coroutine\Client;
use Throttr\SDK\Requests\BaseRequest;
use Throttr\SDK\Requests\InsertRequest;
use Throttr\SDK\Service;
use function Swoole\Coroutine\run;
use Throttr\SDK\Enum\TTLType;
use Throttr\SDK\Enum\ValueSize;

use PHPUnit\Framework\TestCase;


/**
 * @internal
 */
final class ServiceTest extends TestCase
{

    private Service $service;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
    }

    /**
     * Tear down
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->service->close();
    }

    public function testBasicConnection(): void
    {
        $this->prepares(function () {
            $size = getenv('THROTTR_SIZE') ?: 'uint16';
            $valueSize = match ($size) {
                'uint8' => ValueSize::UINT8,
                'uint16' => ValueSize::UINT16,
                'uint32' => ValueSize::UINT32,
                'uint64' => ValueSize::UINT64,
                default => throw new InvalidArgumentException("Unsupported THROTTR_SIZE: $size"),
            };
            $client = new Client(SWOOLE_SOCK_TCP);
            $status = $client->connect('127.0.0.1', 9000);
            $this->assertTrue($status);
            $request = new InsertRequest("ABC", 10, TTLType::SECONDS, 30);
            $client->send($request->toBytes($valueSize));
            $data = $client->recv(7000);
            $this->assertTrue(is_string($data));
            $insert_response = unpack(BaseRequest::pack(ValueSize::UINT8), substr($data, 0, 1));
            $this->assertTrue($insert_response[1] == 0x01 || $insert_response[0] == 0x00);
            $client->close();
        });
    }

    protected function prepares($callback): void
    {
        run(function () use ($callback) {
            $size = getenv('THROTTR_SIZE') ?: 'uint16';

            $valueSize = match ($size) {
                'uint8' => ValueSize::UINT8,
                'uint16' => ValueSize::UINT16,
                'uint32' => ValueSize::UINT32,
                'uint64' => ValueSize::UINT64,
                default => throw new \InvalidArgumentException("Unsupported THROTTR_SIZE: $size"),
            };

            $this->service = new Service('127.0.0.1', 9000, $valueSize, 1);
            $this->service->connect();
            echo "HEY\n";
            $callback();
            echo "LISTEN\n";
            $this->service->close();
            echo "WOW\n";
        });
    }

//    public function testGetAndSet()
//    {
//        $this->prepares(function () {
////            $key = '777777';
////
////            sleep(1);
////
////            $set = $this->service->set(
////                key: $key,
////                ttl: 60,
////                ttlType: TTLType::SECONDS,
////                value: "EHLO"
////            );
////
////            $this->assertTrue($set->success());
////
////            $get = $this->service->get(
////                key: $key,
////            );
////
////            $this->assertTrue($get->success());
////            $this->assertEquals("EHLO", $get->value());
////
////            $purge = $this->service->purge($key);
////            $this->assertTrue($purge->success());
//        });
//    }
}
