<?php
function isPathActive(string $targetUrl): bool
{
    // Get just the path‐portion of any absolute or relative URL, then rawurldecode it
    $targetPath  = rawurldecode(parse_url($targetUrl, PHP_URL_PATH) ?? $targetUrl);
    $currentPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? $_SERVER['REQUEST_URI']);

    // Normalize both: ensure a leading slash, no trailing slash (except for root)
    $normalize = static function (string $path): string {
        $p = '/' . trim($path, '/');
        return $p === '' ? '/' : rtrim($p, '/');
    };

    return $normalize($targetPath) === $normalize($currentPath);
}