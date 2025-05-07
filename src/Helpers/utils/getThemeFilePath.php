<?php
/**
 * Returns the path to the file in the theme directory.
 *
 * @param string $pattern File pattern (for example, "source/components/header-*.php")
 * @return string Path to the file
 */

use Hacon\ThemeCore\Helpers\utils\PatternScanner;
use Hacon\ThemeCore\Services\PathPatternCacheManager\PathPatternCacheManager;

function getThemeFilePath($pattern)
{
    // Delegate pattern resolution to cache manager or direct scanner
    if (PathPatternCacheManager::isActive()) {
        $paths = PathPatternCacheManager::getPaths($pattern);
    } else {
        $paths = PatternScanner::scan($pattern);
    }
    return $paths[0] ?? '';
}