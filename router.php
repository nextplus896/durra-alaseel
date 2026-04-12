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
    $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
}
