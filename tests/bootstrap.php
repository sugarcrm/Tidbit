<?php

// Settings to make all errors more obvious during testing
error_reporting(-1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

define('PROJECT_ROOT', realpath(__DIR__ . '/..'));

require_once PROJECT_ROOT . '/vendor/autoload.php';
require_once PROJECT_ROOT . '/tests/TidbitTestCase.php';
