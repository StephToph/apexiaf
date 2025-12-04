<?php

spl_autoload_register(function ($class) {
    $base_dir = __DIR__ . '/';
    $psr_cache_dir = APPPATH . 'Libraries/Psr/SimpleCache/';

    if (strpos($class, 'PhpOffice\\PhpSpreadsheet\\') === 0) {
        $relative_class = substr($class, strlen('PhpOffice\\PhpSpreadsheet\\'));
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) require_once $file;
    }

    if (strpos($class, 'Psr\\SimpleCache\\') === 0) {
        $relative_class = substr($class, strlen('Psr\\SimpleCache\\'));
        $file = $psr_cache_dir . $relative_class . '.php';
        if (file_exists($file)) require_once $file;
    }
});
