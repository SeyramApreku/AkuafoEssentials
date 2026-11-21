<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/catalog_class.php';

function catalog() { static $c; return $c ??= new CatalogClass(); }
