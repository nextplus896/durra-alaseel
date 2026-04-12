<?php

$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// On Windows, PHP's built-in server cannot follow directory junctions via
// its internal C-level static file sender. We detect that case and serve
// the file manually using readfile() (which DOES follow junctions), so that
// the storage symlink (public/storage -> storage/app/public) works correctly.
if ($uri !== '/' && file_exists($publicPath . $uri)) {
    $fullPath = $publicPath . $uri;

    // Detect if the resolved realpath differs from the literal path —
    // that means we're going through a junction/symlink.
    if (realpath($fullPath) !== $fullPath) {
        $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }

    // Regular static file — let the built-in server handle it.
    return false;
}

require_once $publicPath . '/index.php';
