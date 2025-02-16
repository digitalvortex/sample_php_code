<?php
//// filepath: /Users/michaelkingsnorth/Development/sample_php_code/public/router.php
// If the requested file exists, serve it as-is.
if (php_sapi_name() === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require_once __DIR__ . '/index.php';