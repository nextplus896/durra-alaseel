<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$public = __DIR__ . '/public';

if (preg_match('#^/public/(.*)$#', $uri, $m)) {
    $clean = '/' . $m[1];
    if ($clean !== '/' && file_exists($public . $clean) && is_file($public . $clean)) {
        serveStatic($public . $clean);
    }
    $_SERVER['REQUEST_URI'] = $clean;
}

if ($uri !== '/' && file_exists($public . $uri) && is_file($public . $uri)) {
    serveStatic($public . $uri);
}

if (preg_match('~^/\.~', $uri)) { http_response_code(404); exit; }

require $public . '/index.php';

/**
 * Serve a static file. On Windows, PHP's built-in server cannot follow
 * directory junctions via its C-level static file sender, so we always
 * use readfile() — it uses PHP's filesystem abstraction which is junction-aware.
 */
function serveStatic(string $fullPath): void
{
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $mimeMap = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'mjs'  => 'application/javascript',
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'  => 'font/ttf',
        'otf'  => 'font/otf',
        'eot'  => 'application/vnd.ms-fontobject',
        'json' => 'application/json',
        'map'  => 'application/json',
        'html' => 'text/html; charset=UTF-8',
        'txt'  => 'text/plain',
    ];
    $mime = $mimeMap[$ext] ?? (mime_content_type($fullPath) ?: 'application/octet-stream');
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
}
