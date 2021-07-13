<?php

declare(strict_types=1);

use GSteel\Listless\Octopus\Test\Integration\MockServer;
use GSteel\Listless\Octopus\Util\Assert;

require __DIR__ . '/../../vendor/autoload.php';

$port = $argv[1] ?? 8085;
Assert::numeric($port);

$basePath = $argv[2] ?? '/some/path';

$server = new MockServer((int) $port, $basePath);
$server->start();
