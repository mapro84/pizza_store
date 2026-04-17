#!/usr/bin/env php
<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

(new Dotenv())->loadEnv(__DIR__ . '/../.env');

$kernel = new Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();

$em = $container->get('doctrine.orm.entity_manager');
$hasher = $container->get('security.password_hasher');

require_once __DIR__ . '/OrderWorkflowTest.php';
require_once __DIR__ . '/AuthenticationTest.php';

$config = require __DIR__ . '/config.php';

$argument = $argv[1] ?? 'all';

if ($argument === 'auth' || $argument === 'all') {
    echo "\n";
    echo str_repeat('=', 60) . "\n";
    echo "AUTHENTICATION TESTS\n";
    echo str_repeat('=', 60) . "\n\n";

    $authTest = new AuthenticationTest($em, $hasher, $config);
    $authResults = $authTest->runAll();
    AuthenticationTest::printResults($authResults);
}

if ($argument === 'order' || $argument === 'all') {
    echo "\n";
    echo str_repeat('=', 60) . "\n";
    echo "ORDER WORKFLOW TESTS\n";
    echo str_repeat('=', 60) . "\n\n";

    $orderTest = new OrderWorkflowTest($em, $hasher, $config);
    $orderTest->runAll();
}

if ($argument === 'all') {
    echo "\n";
    echo str_repeat('=', 60) . "\n";
    echo "ALL TESTS COMPLETED\n";
    echo str_repeat('=', 60) . "\n";
}
