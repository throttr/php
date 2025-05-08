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

namespace Throttr\tests;

use PHPUnit\Framework\TestCase;
use Throttr\SDK\Enum\AttributeType;
use Throttr\SDK\Enum\ChangeType;
use Throttr\SDK\Enum\TTLType;
use Throttr\SDK\Enum\ValueSize;
use Throttr\SDK\Service;

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

    /**
     * Insert and query
     *
     * @return void
     */
    public function testCompatibilityWithThrottrServer(): void
    {
        $key = '333333';

        sleep(1);

        $insert = $this->service->insert(
            key: $key,
            ttl: 60,
            ttlType: TTLType::SECONDS,
            quota: 7
        );

        $this->assertIsBool($insert->success());
        $this->assertTrue($insert->success(), 'Insert should be successful');

        $firstQuery = $this->service->query($key);
        $this->assertTrue($firstQuery->success());
        $this->assertEquals(7, $firstQuery->quota());
        $this->assertEquals(TTLType::SECONDS, $firstQuery->ttlType());
        $this->assertGreaterThan(0, $firstQuery->ttl());
        $this->assertLessThan(60, $firstQuery->ttl());

        $update1 = $this->service->update($key, AttributeType::QUOTA, ChangeType::DECREASE, 7);
        $this->assertTrue($update1->success());

        $update2 = $this->service->update($key, AttributeType::QUOTA, ChangeType::DECREASE, 7);
        $this->assertFalse($update2->success());

        $queryAfterDecrease = $this->service->query($key);
        $this->assertTrue($queryAfterDecrease->success());
        $this->assertEquals(0, $queryAfterDecrease->quota());
        $this->assertEquals(TTLType::SECONDS, $queryAfterDecrease->ttlType());
        $this->assertGreaterThan(0, $queryAfterDecrease->ttl());
        $this->assertLessThan(60, $queryAfterDecrease->ttl());

        $patch = $this->service->update($key, AttributeType::QUOTA, ChangeType::PATCH, 10);
        $this->assertTrue($patch->success());

        $queryAfterPatch = $this->service->query($key);
        $this->assertTrue($queryAfterPatch->success());
        $this->assertEquals(10, $queryAfterPatch->quota());

        $increase = $this->service->update($key, AttributeType::QUOTA, ChangeType::INCREASE, 20);
        $this->assertTrue($increase->success());

        $queryAfterIncrease = $this->service->query($key);
        $this->assertTrue($queryAfterIncrease->success());
        $this->assertEquals(30, $queryAfterIncrease->quota());

        $ttlIncrease = $this->service->update($key, AttributeType::TTL, ChangeType::INCREASE, 60);
        $this->assertTrue($ttlIncrease->success());

        $queryAfterTtlIncrease = $this->service->query($key);
        $this->assertTrue($queryAfterTtlIncrease->success());
        $this->assertGreaterThan(60, $queryAfterTtlIncrease->ttl());
        $this->assertLessThan(120, $queryAfterTtlIncrease->ttl());

        $ttlDecrease = $this->service->update($key, AttributeType::TTL, ChangeType::DECREASE, 60);
        $this->assertTrue($ttlDecrease->success());

        $queryAfterTtlDecrease = $this->service->query($key);
        $this->assertTrue($queryAfterTtlDecrease->success());
        $this->assertGreaterThan(0, $queryAfterTtlDecrease->ttl());
        $this->assertLessThan(60, $queryAfterTtlDecrease->ttl());

        $ttlPatch = $this->service->update($key, AttributeType::TTL, ChangeType::PATCH, 90);
        $this->assertTrue($ttlPatch->success());

        $queryAfterTtlPatch = $this->service->query($key);
        $this->assertTrue($queryAfterTtlPatch->success());
        $this->assertGreaterThan(60, $queryAfterTtlPatch->ttl());
        $this->assertLessThan(90, $queryAfterTtlPatch->ttl());

        $purge = $this->service->purge($key);
        $this->assertTrue($purge->success());

        $purgeAgain = $this->service->purge($key);
        $this->assertFalse($purgeAgain->success());

        $queryFinal = $this->service->query($key);
        $this->assertFalse($queryFinal->success());
    }


    /**
     * Update and purge
     *
     * @return void
     */
    public function testUpdate(): void
    {
        $key = 'someone';

        $insertResponse = $this->service->insert(
            key: $key,
            ttl: 3,
            ttlType: TTLType::SECONDS,
            quota: 10,
        );

        $this->assertTrue($insertResponse->success(), 'Insert should be successful');

        $updateResponse = $this->service->update(
            key: $key,
            attribute: AttributeType::QUOTA,
            change: ChangeType::INCREASE,
            value: 5
        );

        $this->assertTrue($updateResponse->success(), 'Update should be successful');

        $purgeResponse = $this->service->purge(
            key: $key,
        );

        $this->assertTrue($purgeResponse->success(), 'Purge should be successful');
    }
}
