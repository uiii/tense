<?php

define("PROJECT_DIR", __DIR__ . '/..');
define("SRC_DIR", PROJECT_DIR . '/src');
define("TEST_DIR", PROJECT_DIR . '/tests');
define("FIXTURE_DIR", TEST_DIR . '/fixture');

require PROJECT_DIR . '/vendor/autoload.php';

Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');
