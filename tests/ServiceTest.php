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
        $this->service = new Service('127.0.0.1', 9000, ValueSize::UINT16, 1);
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
        $key = '127.0.0.1:33';

        $insertResponse = $this->service->insert(
            key: $key,
            ttl: 3,
            ttlType: TTLType::SECONDS,
            quota: 10,
        );

        $this->assertTrue($insertResponse->success(), 'Insert should be successful');

        $queryResponse = $this->service->query(
            key: $key,
        );

        $this->assertTrue($queryResponse->success(), 'Query should be successful');
        $this->assertGreaterThanOrEqual(0, $queryResponse->quotaRemaining(), 'Quota should be non-negative');
        $this->assertGreaterThanOrEqual(0, $queryResponse->ttlRemaining(), 'TTL should be non-negative');
        $this->assertLessThanOrEqual(3, $queryResponse->ttlRemaining(), 'TTL should be less than 3 seconds');
    }

//    public function testBatching()
//    {
//
//        $response = $this->service->send([
//            new \Throttr\SDK\Requests\InsertRequest("abc", 60, TTLType::SECONDS, 60),
//        ]);
//        var_dump($response);
//
//    }

    /**
     * Update and purge
     *
     * @return void
     */
    public function testUpdate(): void
    {
//        $key = 'someone';
//
//        $insertResponse = $this->service->insert(
//            key: $key,
//            ttl: 3,
//            ttlType: TTLType::SECONDS,
//            quota: 10,
//        );
//
//        $this->assertTrue($insertResponse->success(), 'Insert should be successful');
//
//        $updateResponse = $this->service->update(
//            key: $key,
//            attribute: AttributeType::QUOTA,
//            change: ChangeType::INCREASE,
//            value: 5
//        );
//
//        $this->assertTrue($updateResponse->success(), 'Update should be successful');
//
//        $purgeResponse = $this->service->purge(
//            key: $key,
//        );
//
//        $this->assertTrue($purgeResponse->success(), 'Purge should be successful');
    }
}
