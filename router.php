<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$public = __DIR__ . '/public';

if (preg_match('#^/public/(.*)$#', $uri, $m)) {
    $clean = '/' . $m[1];
    if ($clean !== '/' && file_exists($public . $clean) && is_file($public . $clean)) {
        return false;
    }
    $_SERVER['REQUEST_URI'] = $clean;
}

if ($uri !== '/' && file_exists($public . $uri) && is_file($public . $uri)) {
    return false;
}

if (preg_match('~^/\.~', $uri)) { http_response_code(404); exit; }

require $public . '/index.php';
