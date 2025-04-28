# Throttr PHP SDK

<p align="center">
<a href="https://github.com/throttr/php/actions/workflows/build.yml"><img src="https://github.com/throttr/throttr/actions/workflows/build.yml/badge.svg" alt="Build"></a>
<a href="https://codecov.io/gh/throttr/php"><img src="https://codecov.io/gh/throttr/php/graph/badge.svg?token=5TSHBIYUBI" alt="Coverage"></a>
<a href="https://sonarcloud.io/project/overview?id=throttr_php"><img src="https://sonarcloud.io/api/project_badges/measure?project=throttr_php&metric=alert_status" alt="Quality Gate"></a>
</p>

<p align="center">
<a href="https://sonarcloud.io/project/overview?id=throttr_php"><img src="https://sonarcloud.io/api/project_badges/measure?project=throttr_php&metric=bugs" alt="Bugs"></a>
<a href="https://sonarcloud.io/project/overview?id=throttr_php"><img src="https://sonarcloud.io/api/project_badges/measure?project=throttr_php&metric=vulnerabilities" alt="Vulnerabilities"></a>
<a href="https://sonarcloud.io/project/overview?id=throttr_php"><img src="https://sonarcloud.io/api/project_badges/measure?project=throttr_php&metric=code_smells" alt="Code Smells"></a>
<a href="https://sonarcloud.io/project/overview?id=throttr_php"><img src="https://sonarcloud.io/api/project_badges/measure?project=throttr_php&metric=duplicated_lines_density" alt="Duplicated Lines"></a>
<a href="https://sonarcloud.io/project/overview?id=throttr_php"><img src="https://sonarcloud.io/api/project_badges/measure?project=throttr_php&metric=sqale_index" alt="Technical Debt"></a>
</p>

<p align="center">
<a href="https://sonarcloud.io/project/overview?id=throttr_php"><img src="https://sonarcloud.io/api/project_badges/measure?project=throttr_php&metric=reliability_rating" alt="Reliability"></a>
<a href="https://sonarcloud.io/project/overview?id=throttr_php"><img src="https://sonarcloud.io/api/project_badges/measure?project=throttr_php&metric=security_rating" alt="Security"></a>
<a href="https://sonarcloud.io/project/overview?id=throttr_php"><img src="https://sonarcloud.io/api/project_badges/measure?project=throttr_throttr&metric=sqale_rating" alt="Maintainability"></a>
</p>

php client for communicating with a Throttr server over TCP.

The SDK enables sending traffic control requests efficiently, without HTTP, respecting the server's native binary protocol.

## Installation

Add the dependency using Composer:

```bash
composer require throttr/sdk
```

## Basic Usage

```php
<?php

require 'vendor/autoload.php';

use Throttr\SDK\Service;
use Throttr\SDK\Enum\TTLType;

// Configure your instance with 4 connections ...
$service = new Service('127.0.0.1', 9000, 4);

// Define a consumer ... it can be an IP and port or UUID, whatever ...
$consumerId = "127.0.0.1";

// Define the resource ... it can be a METHOD + URL or UUID, whatever ...
$resourceId = "/api/resource";

// Connect to Throttr
$service->connect();

// Add limit to the registry
$service->insert(
    consumerId: $consumerId,
    resourceId: $resourceId,
    ttl: 3000,
    ttlType: TTLType::MILLISECONDS,
    quota: 5,
    usage: 0
);

// Do you want to know if that was stored?
$response = $service->query(
    consumerId: $consumerId,
    resourceId: $resourceId,
);

printf(
    "Allowed: %s, Remaining: %d, TTL: %dms\n",
    $response->can() ? 'true' : 'false',
    $response->quotaRemaining() ?? 0,
    (int)($response->ttlRemainingSeconds() * 1000)
);

// Do you want to update the quota?
$service->update(
    consumerId: $consumerId,
    resourceId: $resourceId,
    attribute: AttributeType::QUOTA,
    change: ChangeType::DECREASE,
    value: 1
);

// Do you want to know the new value?
$response = $service->query(
    consumerId: $consumerId,
    resourceId: $resourceId,
);

printf(
    "Allowed: %s, Remaining: %d, TTL: %dms\n",
    $response->can() ? 'true' : 'false',
    $response->quotaRemaining() ?? 0,
    (int)($response->ttlRemainingSeconds() * 1000)
);

// Close the connections ...
$service->close();
```

## Technical Notes

- The protocol assumes Little Endian architecture.
- The internal message queue ensures requests are processed sequentially.

---

## License

Distributed under the [GNU Affero General Public License v3.0](./LICENSE).