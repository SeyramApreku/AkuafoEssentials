<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/catalog_class.php';

function catalog() { static $c; return $c ??= new CatalogClass(); }

require_once __DIR__ . '/../classes/entertaining_class.php';
function entertaining() { static $c; return $c ??= new EntertainingClass(); }
