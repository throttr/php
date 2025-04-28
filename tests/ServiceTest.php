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

use PHPUnit\Framework\TestCase;
use Throttr\SDK\Service;
use Throttr\SDK\Enum\TTLType;
use Throttr\SDK\Enum\AttributeType;
use Throttr\SDK\Enum\ChangeType;

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
        $this->service = new Service('127.0.0.1', 9000, 1);
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
    public function testInsertAndQuery(): void
    {
        $consumerId = '127.0.0.1:33';
        $resourceId = 'GET /api';

        $insertResponse = $this->service->insert(
            consumerId: $consumerId,
            resourceId: $resourceId,
            ttl: 3,
            ttlType: TTLType::SECONDS,
            quota: 10,
            usage: 0
        );

        $this->assertTrue($insertResponse->can(), 'Insert should be successful');

        $queryResponse = $this->service->query(
            consumerId: $consumerId,
            resourceId: $resourceId
        );

        $this->assertTrue($queryResponse->can(), 'Query should be successful');
        $this->assertGreaterThanOrEqual(0, $queryResponse->quotaRemaining(), 'Quota should be non-negative');
        $this->assertGreaterThanOrEqual(0, $queryResponse->ttlRemaining(), 'TTL should be non-negative');
        $this->assertLessThanOrEqual(3, $queryResponse->ttlRemaining(), 'TTL should be less than 3 seconds');
    }

    /**
     * Update and purge
     *
     * @return void
     */
    public function testUpdate(): void
    {
        $consumerId = 'someone';
        $resourceId = '/updatable';

        $insertResponse = $this->service->insert(
            consumerId: $consumerId,
            resourceId: $resourceId,
            ttl: 3,
            ttlType: TTLType::SECONDS,
            quota: 10,
            usage: 0
        );

        $this->assertTrue($insertResponse->can(), 'Insert should be successful');

        $updateResponse = $this->service->update(
            consumerId: $consumerId,
            resourceId: $resourceId,
            attribute: AttributeType::QUOTA,
            change: ChangeType::INCREASE,
            value: 5
        );

        $this->assertTrue($updateResponse->success(), 'Update should be successful');

        $purgeResponse = $this->service->purge(
            consumerId: $consumerId,
            resourceId: $resourceId
        );

        $this->assertTrue($purgeResponse->success(), 'Purge should be successful');
    }
}
