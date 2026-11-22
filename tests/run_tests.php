<?php
// Runs every test file in this folder, each in its own PHP process, and prints a summary.
//   php tests/run_tests.php
// Exit code 0 = everything passed, 1 = something failed.
$files = array_merge(glob(__DIR__ . '/*_test.php'), [__DIR__ . '/run_cart_tests.php']);
sort($files);
$php = PHP_BINARY;
$totalPassed = 0; $totalFailed = 0; $broken = [];

foreach ($files as $file) {
    $name = basename($file);
    echo "\n===== $name =====\n";
    $out = [];
    exec(escapeshellarg($php) . ' ' . escapeshellarg($file) . ' 2>&1', $out, $code);
    $lines = array_filter($out, fn($l) => !preg_match('/^PHP warning: Constant \w+ already defined/', $l));   // noise from test setup
    echo implode("\n", $lines), "\n";
    if (preg_match('/Results: (\d+) passed, (\d+) failed/', implode("\n", $out), $m)) {
        $totalPassed += (int)$m[1]; $totalFailed += (int)$m[2];
    }
    if ($code !== 0) $broken[] = $name;
}

echo "\n=====================================\n";
echo "TOTAL: $totalPassed passed, $totalFailed failed across " . count($files) . " files\n";
if ($broken) echo 'Files with failures: ' . implode(', ', $broken) . "\n";
exit($broken ? 1 : 0);
