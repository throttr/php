# Throttr SDK for PHP

<p align="center">
<a href="https://github.com/throttr/php/actions/workflows/build.yml"><img src="https://github.com/throttr/php/actions/workflows/build.yml/badge.svg" alt="Build"></a>
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

## 🛠️ Installation

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
use Throttr\SDK\Enum\AttributeType;
use Throttr\SDK\Enum\ChangeType;
use Throttr\SDK\Enum\ValueSize;

// Configure your instance with 4 connections and a value size (e.g. UINT16)
$service = new Service('127.0.0.1', 9000, ValueSize::UINT16, 4);

// Define the key ... it can be an IP+port, UUID, route, etc.
$key = '127.0.0.1:/api/resource';

// Connect to Throttr
$service->connect();

// Insert a rule for this key
$service->insert(
    key: $key,
    ttl: 3000,
    ttlType: TTLType::MILLISECONDS,
    quota: 5
);

// Query the current state
$response = $service->query($key);

printf(
    "Allowed: %s, Remaining: %d, TTL: %dms\n",
    $response->success() ? 'true' : 'false',
    $response->quota() ?? 0,
    (int)($response->ttl() ?? 0)
);

// Update the quota (consume 1)
$service->update(
    key: $key,
    attribute: AttributeType::QUOTA,
    change: ChangeType::DECREASE,
    value: 1
);

// Query again
$response = $service->query($key);

printf(
    "Allowed: %s, Remaining: %d, TTL: %dms\n",
    $response->success() ? 'true' : 'false',
    $response->quota() ?? 0,
    (int)($response->ttl() ?? 0)
);

// Close connections
$service->close();
```

## Technical Notes

- The protocol assumes Little Endian architecture.
- The internal message queue ensures requests are processed sequentially.
- The package is defined to works with protocol 4.0.9 or greatest.

---

## License

Distributed under the [GNU Affero General Public License v3.0](./LICENSE).
