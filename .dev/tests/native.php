<?php

declare(strict_types=1);

$tests = require dirname(__DIR__, 2) . '/tests/module.php';
foreach ($tests as $name => $test) {
    $test();
    echo "PASS: $name\n";
}
