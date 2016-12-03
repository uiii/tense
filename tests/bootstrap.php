<?php

define("PROJECT_DIR", __DIR__ . '/..');
define("TEST_DIR", PROJECT_DIR . '/tests');
define("SRC_DIR", PROJECT_DIR . '/src');

require PROJECT_DIR . '/vendor/autoload.php';

Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');
