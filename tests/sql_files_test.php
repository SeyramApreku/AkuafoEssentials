<?php
// The SQL files run by Admin > Database Update: they must split correctly and be safe to run twice.
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../classes/setup_class.php';

section('Splitting SQL into statements');
checkTest('Two simple statements', count(SetupClass::splitSql("SELECT 1; SELECT 2;")), 2);
checkTest('Semicolon inside quotes is kept', SetupClass::splitSql("INSERT INTO t VALUES ('a;b'); SELECT 1")[0], "INSERT INTO t VALUES ('a;b')");
checkTest('Doubled quotes inside a string', count(SetupClass::splitSql("SELECT 'it''s; fine'; SELECT 2")), 2);
checkTest('-- comments are dropped', SetupClass::splitSql("-- note; with semicolon\nSELECT 1;"), ['SELECT 1']);
checkTest('# comments are dropped', SetupClass::splitSql("# note;\nSELECT 1;"), ['SELECT 1']);
checkTest('Empty input', SetupClass::splitSql("  \n  "), []);

section('Every update file is safe to run more than once');
$setup = (new ReflectionClass('SetupClass'))->newInstanceWithoutConstructor();
foreach ($setup->steps() as $step) {
    $path = __DIR__ . '/../db/' . $step['file'];
    checkTest($step['file'] . ' exists', is_file($path), true);
    if (!is_file($path)) continue;
    $stmts = SetupClass::splitSql(file_get_contents($path));
    checkTest($step['file'] . ' has statements', count($stmts) > 0, true);
    $unsafe = array_filter($stmts, fn($s) =>
        preg_match('/^CREATE TABLE (?!IF NOT EXISTS)/i', $s)       // must be CREATE TABLE IF NOT EXISTS
        || preg_match('/^ALTER TABLE/i', $s)                       // columns are added through the guarded PREPARE pattern
        || preg_match('/^INSERT INTO /i', $s) && !preg_match('/ON DUPLICATE KEY|WHERE @\w+ = 0\s*$/i', $s)   // plain INSERT needs a guard
        || preg_match('/^(DROP|TRUNCATE|DELETE)\b/i', $s));        // never removes data
    checkTest($step['file'] . ' has no unguarded or destructive statements', array_values($unsafe), []);
}

finish();
