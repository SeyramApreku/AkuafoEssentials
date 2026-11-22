<?php
// Shared setup for the unit tests. Each *_test.php file is run on its own by run_tests.php.
// These tests use no database: they check the logic the website relies on.
error_reporting(E_ALL);
$_SERVER['HTTP_HOST'] = 'localhost';          // site_url() and friends expect a host
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once __DIR__ . '/../core/core.php';

$GLOBALS['passed'] = 0;
$GLOBALS['failed'] = 0;

// Same style as run_cart_tests.php: checkTest(name, actual, expected) compares with ===.
function checkTest($name, $actual, $expected)
{
    if ($actual === $expected) { echo "PASS: $name\n"; $GLOBALS['passed']++; return; }
    echo "FAIL: $name (expected " . var_export($expected, true) . ', got ' . var_export($actual, true) . ")\n";
    $GLOBALS['failed']++;
}

function section($title) { echo "\n-- $title --\n"; }

// Call at the end of every test file: prints the totals and sets the exit code.
function finish()
{
    echo "\nResults: {$GLOBALS['passed']} passed, {$GLOBALS['failed']} failed\n";
    exit($GLOBALS['failed'] ? 1 : 0);
}
