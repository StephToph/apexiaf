<?php

// --------------------------------------------------------------------
// Path to the front controller (this file)
// --------------------------------------------------------------------
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// --------------------------------------------------------------------
// Ensure we're running from the correct directory
// --------------------------------------------------------------------
chdir(__DIR__);

// --------------------------------------------------------------------
// Load the Paths configuration file (one level ABOVE /public)
// --------------------------------------------------------------------
$pathsConfig = realpath(FCPATH . '../app/Config/Paths.php');
if ($pathsConfig === false) {
    exit('Paths.php not found. Check your folder structure.');
}
require $pathsConfig;

// Instantiate the Paths class
$paths = new Config\Paths();

// --------------------------------------------------------------------
// Load the CodeIgniter bootstrap file
// --------------------------------------------------------------------
$bootstrap = realpath($paths->systemDirectory . '/bootstrap.php');
if ($bootstrap === false) {
    exit('System bootstrap file not found: ' . $paths->systemDirectory);
}

// Boot the app
$app = require $bootstrap;

// --------------------------------------------------------------------
// Run the application
// --------------------------------------------------------------------
$app->run();
