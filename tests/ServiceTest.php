<?php declare(strict_types=1);

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

    protected function setUp(): void
    {
        $this->service = new Service('127.0.0.1', 9000, 1);
        $this->service->connect();
    }

    protected function tearDown(): void
    {
        $this->service->close();
    }

    public function testInsertAndQuery(): void
    {
        $insertResponse = $this->service->insert(
            consumerId: 'realuser',
            resourceId: '/real/resource',
            ttl: 3,
            ttlType: TTLType::SECONDS,
            quota: 10,
            usage: 0
        );

        $this->assertTrue($insertResponse->can(), 'Insert should be successful');

        $queryResponse = $this->service->query(
            consumerId: 'realuser',
            resourceId: '/real/resource'
        );

        $this->assertTrue($queryResponse->can(), 'Query should be successful');
        $this->assertGreaterThanOrEqual(0, $queryResponse->quotaRemaining(), 'Quota should be non-negative');
        $this->assertGreaterThanOrEqual(0, $queryResponse->ttlRemaining(), 'TTL should be non-negative');
        $this->assertLessThanOrEqual(3, $queryResponse->ttlRemaining(), 'TTL should be less than 3 seconds');
    }

    public function testUpdate(): void
    {
        $insertResponse = $this->service->insert(
            consumerId: 'otheruser',
            resourceId: '/real/resource',
            ttl: 3,
            ttlType: TTLType::SECONDS,
            quota: 10,
            usage: 0
        );

        $this->assertTrue($insertResponse->can(), 'Insert should be successful');

        $updateResponse = $this->service->update(
            consumerId: 'otheruser',
            resourceId: '/real/resource',
            attribute: AttributeType::QUOTA,
            change: ChangeType::INCREASE,
            value: 5
        );

        $this->assertTrue($updateResponse->success(), 'Update should be successful');

        $purgeResponse = $this->service->purge(
            consumerId: 'otheruser',
            resourceId: '/real/resource'
        );

        $this->assertTrue($purgeResponse->success(), 'Purge should be successful');
    }
}
