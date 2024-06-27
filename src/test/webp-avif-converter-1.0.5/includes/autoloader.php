<?php

spl_autoload_register(function ($class) {
    $prefix = 'WebpAvifConverter\\';
    $base_dir = __DIR__;
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $relative_class_path = str_replace('\\', '/', $relative_class) . '.php';
    
    $file_paths = [
        $base_dir . '/interfaces/' . $relative_class_path,
        $base_dir . '/classes/' . $relative_class_path,
    ];
    
    foreach ($file_paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
