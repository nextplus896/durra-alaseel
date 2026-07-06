<?php

$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

if ($uri !== '/' && file_exists($publicPath . $uri) && is_file($publicPath . $uri)) {
    $fullPath = $publicPath . $uri;
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

    // On Windows, realpath() normalises separators to backslashes while
    // $fullPath uses a forward-slash URI suffix, so the two strings are never
    // equal. That makes the old "is it a symlink?" check unreliable and causes
    // mime_content_type() (which returns 'text/plain' for CSS/JS on Windows)
    // to be used for every static asset. We bypass both problems by using an
    // explicit MIME map and always serving known assets via readfile().
    $mimeMap = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'mjs'   => 'application/javascript',
        'svg'   => 'image/svg+xml',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'webp'  => 'image/webp',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'otf'   => 'font/otf',
        'eot'   => 'application/vnd.ms-fontobject',
        'json'  => 'application/json',
        'map'   => 'application/json',
        'txt'   => 'text/plain',
        'html'  => 'text/html; charset=UTF-8',
    ];

    if (isset($mimeMap[$ext])) {
        header('Content-Type: ' . $mimeMap[$ext]);
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }

    // Unknown extension — fall back to the built-in server's static handler.
    return false;
}

require_once $publicPath . '/index.php';
