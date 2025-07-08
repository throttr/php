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

use Throttr\SDK\Enum\AttributeType;
use Throttr\SDK\Enum\ChangeType;
use Throttr\SDK\Enum\KeyType;
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
    protected function prepares($callback): void
    {
        run(function () use ($callback) {
            $size = getenv('THROTTR_SIZE') ?: 'uint16';

            $valueSize = match ($size) {
                'uint8' => ValueSize::UINT8,
                'uint16' => ValueSize::UINT16,
                'uint32' => ValueSize::UINT32,
                'uint64' => ValueSize::UINT64,
                default => throw new InvalidArgumentException("Unsupported THROTTR_SIZE: $size"),
            };

            $service = new Service('127.0.0.1', 9000, $valueSize, 1);
            $service->connect();
            $callback($service);
            $service->close();
        });
    }

    public function testProtocolCompatibility()
    {
        $this->prepares(function (Service $service) {
            $key = 'TEST-PROTOCOL-COMPATIBILITY';

            $insert = $service->insert(
                key: $key,
                ttl: 60,
                ttlType: TTLType::SECONDS,
                quota: 7
            );

            $this->assertIsBool($insert->status);
            $this->assertTrue($insert->status, 'Insert should be successful');

            $firstQuery = $service->query($key);
            $this->assertTrue($firstQuery->status);
            $this->assertEquals(7, $firstQuery->quota);
            $this->assertEquals(TTLType::SECONDS, $firstQuery->ttl_type);
            $this->assertGreaterThan(0, $firstQuery->ttl);
            $this->assertLessThan(60, $firstQuery->ttl);

            $update1 = $service->update($key, AttributeType::QUOTA, ChangeType::DECREASE, 7);
            $this->assertTrue($update1->status);

            $update2 = $service->update($key, AttributeType::QUOTA, ChangeType::DECREASE, 7);
            $this->assertFalse($update2->status);

            $queryAfterDecrease = $service->query($key);
            $this->assertTrue($queryAfterDecrease->status);
            $this->assertEquals(0, $queryAfterDecrease->quota);
            $this->assertEquals(TTLType::SECONDS, $queryAfterDecrease->ttl_type);
            $this->assertGreaterThan(0, $queryAfterDecrease->ttl);
            $this->assertLessThan(60, $queryAfterDecrease->ttl);

            $patch = $service->update($key, AttributeType::QUOTA, ChangeType::PATCH, 10);
            $this->assertTrue($patch->status);

            $queryAfterPatch = $service->query($key);
            $this->assertTrue($queryAfterPatch->status);
            $this->assertEquals(10, $queryAfterPatch->quota);

            $increase = $service->update($key, AttributeType::QUOTA, ChangeType::INCREASE, 20);
            $this->assertTrue($increase->status);

            $queryAfterIncrease = $service->query($key);
            $this->assertTrue($queryAfterIncrease->status);
            $this->assertEquals(30, $queryAfterIncrease->quota);

            $ttlIncrease = $service->update($key, AttributeType::TTL, ChangeType::INCREASE, 60);
            $this->assertTrue($ttlIncrease->status);

            $queryAfterTtlIncrease = $service->query($key);
            $this->assertTrue($queryAfterTtlIncrease->status);
            $this->assertGreaterThan(60, $queryAfterTtlIncrease->ttl);
            $this->assertLessThan(120, $queryAfterTtlIncrease->ttl);

            $ttlDecrease = $service->update($key, AttributeType::TTL, ChangeType::DECREASE, 60);
            $this->assertTrue($ttlDecrease->status);

            $queryAfterTtlDecrease = $service->query($key);
            $this->assertTrue($queryAfterTtlDecrease->status);
            $this->assertGreaterThan(0, $queryAfterTtlDecrease->ttl);
            $this->assertLessThan(60, $queryAfterTtlDecrease->ttl);

            $ttlPatch = $service->update($key, AttributeType::TTL, ChangeType::PATCH, 90);
            $this->assertTrue($ttlPatch->status);

            $queryAfterTtlPatch = $service->query($key);
            $this->assertTrue($queryAfterTtlPatch->status);
            $this->assertGreaterThan(60, $queryAfterTtlPatch->ttl);
            $this->assertLessThan(90, $queryAfterTtlPatch->ttl);

            $purge = $service->purge($key);
            $this->assertTrue($purge->status);

            $purgeAgain = $service->purge($key);
            $this->assertFalse($purgeAgain->status);

            $queryFinal = $service->query($key);
            $this->assertFalse($queryFinal->status);
        });
    }

    public function testGetAndSet() {
        $this->prepares(function (Service $service) {
            $key = 'TEST-GET-AND-SET';

            $set = $service->set(
                key: $key,
                ttl: 60,
                ttlType: TTLType::SECONDS,
                value: "EHLO"
            );

            $this->assertTrue($set->status);

            $get = $service->get(
                key: $key,
            );

            $this->assertTrue($get->status);
            $this->assertEquals("EHLO", $get->value);

            $purge = $service->purge($key);
            $this->assertTrue($purge->status);

            $get = $service->get(
                key: $key,
            );

            $this->assertFalse($get->status);
        });
    }

    public function testUpdate() {
        $this->prepares(function (Service $service) {
            $key = 'TEST-UPDATE';

            $insertResponse = $service->insert(
                key: $key,
                ttl: 3,
                ttlType: TTLType::SECONDS,
                quota: 10,
            );

            $this->assertTrue($insertResponse->status);

            $updateResponse = $service->update(
                key: $key,
                attribute: AttributeType::QUOTA,
                change: ChangeType::INCREASE,
                value: 5
            );

            $this->assertTrue($updateResponse->status);

            $purgeResponse = $service->purge(
                key: $key,
            );

            $this->assertTrue($purgeResponse->status);

            $get = $service->get(
                key: $key,
            );

            $this->assertFalse($get->status);
        });
    }

    public function testList() {
        $this->prepares(function (Service $service) {
            $key = 'LIST_KEY';
            $list = $service->list();
            $this->assertTrue($list->status);
            $this->assertCount(0, $list->keys);

            $service->insert(
                key: $key,
                ttl: 3,
                ttlType: TTLType::SECONDS,
                quota: 10,
            );

            $list = $service->list();

            $this->assertTrue($list->status);
            $this->assertCount(1, $list->keys);
            $this->assertEquals($key, $list->keys[0]["key"]);
            $this->assertEquals($service->size->value, $list->keys[0]["bytes_used"]);
            $this->assertEquals(KeyType::COUNTER, $list->keys[0]["type"]);
            $this->assertEquals(TTLType::SECONDS, $list->keys[0]["ttl_type"]);

            $service->purge(
                key: $key,
            );

            $list = $service->list();
            $this->assertTrue($list->status);
            $this->assertCount(0, $list->keys);
        });
    }

    public function testFragmentedList() {
        $this->prepares(function (Service $service) {
            $list = $service->list();
            $this->assertTrue($list->status);
            $this->assertCount(0, $list->keys);

            $keys = [];

            for ($i = 0; $i < 1000; $i++) {
                $key = "TEST_FRAGMENTED_LIST_$i";
                $insertResponse = $service->insert(
                    key: $key,
                    ttl: 3,
                    ttlType: TTLType::SECONDS,
                    quota: 10,
                );
                $keys[] = $key;

                $this->assertTrue($insertResponse->status);
            }

            $list = $service->list();

            $this->assertTrue($list->status);
            $this->assertCount(1000, $list->keys);

            foreach ($keys as $key) {
                $service->purge(
                    key: $key,
                );
            }

            $list = $service->list();
            $this->assertTrue($list->status);
            $this->assertCount(0, $list->keys);
        });
    }

    public function testInfo()
    {
        $this->prepares(function (Service $service) {
            $info = $service->info();

            $this->assertTrue($info->status);
            $this->assertArrayHasKey("now", $info->attributes);
            $this->assertArrayHasKey("total_requests", $info->attributes);
            $this->assertArrayHasKey("total_requests_per_minute", $info->attributes);
            $this->assertArrayHasKey("requests", $info->attributes);

            foreach ([
                         'INSERT',
                         'QUERY',
                         'UPDATE',
                         'PURGE',
                         'GET',
                         'SET',
                         'LIST',
                         'INFO',
                         'STATS',
                         'STAT',
                         'SUBSCRIBE',
                         'UNSUBSCRIBE',
                         'PUBLISH',
                         'CHANNEL',
                         'CHANNELS',
                         'WHOAMI',
                         'CONNECTION',
                         'CONNECTIONS',
                     ] as $type) {
                $this->assertArrayHasKey($type, $info->attributes["requests"]);
                $this->assertArrayHasKey("total", $info->attributes["requests"][$type]);
                $this->assertArrayHasKey("per_minute", $info->attributes["requests"][$type]);
            }

            $this->assertArrayHasKey("total_read", $info->attributes);
            $this->assertArrayHasKey("total_read_per_minute", $info->attributes);
            $this->assertArrayHasKey("total_write", $info->attributes);
            $this->assertArrayHasKey("total_write_per_minute", $info->attributes);
            $this->assertArrayHasKey("total_keys", $info->attributes);
            $this->assertArrayHasKey("total_counters", $info->attributes);
            $this->assertArrayHasKey("total_buffers", $info->attributes);
            $this->assertArrayHasKey("total_allocated_bytes_on_counters", $info->attributes);
            $this->assertArrayHasKey("total_allocated_bytes_on_buffers", $info->attributes);
            $this->assertArrayHasKey("total_subscriptions", $info->attributes);
            $this->assertArrayHasKey("total_channels", $info->attributes);
            $this->assertArrayHasKey("started_at", $info->attributes);
            $this->assertArrayHasKey("total_connections", $info->attributes);
            $this->assertArrayHasKey("version", $info->attributes);
        });
    }
}
