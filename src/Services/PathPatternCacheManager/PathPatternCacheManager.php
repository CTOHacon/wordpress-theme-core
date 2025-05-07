<?php
namespace Hacon\ThemeCore\Services\PathPatternCacheManager;

use Hacon\ThemeCore\Helpers\utils\PatternScanner;

/**
 * Caches filesystem results for wildcard import patterns.
 */
class PathPatternCacheManager
{
    private static string $cacheFile = '';
    private static bool   $active    = false;

    /**
     * Activate caching (e.g. called from module init)
     */
    public static function activate(): void
    {
        if (self::$cacheFile === '') {
            self::$cacheFile = get_template_directory() . '/theme-require-mapping.json';
        }
        self::$active = true;
    }

    /**
     * Check if cache is enabled
     */
    public static function isActive(): bool
    {
        return self::$active;
    }

    private static function loadCache(): array
    {
        if (!file_exists(self::$cacheFile)) {
            return [];
        }
        $json = file_get_contents(self::$cacheFile);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private static function saveCache(array $data): void
    {
        file_put_contents(self::$cacheFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    private static function getCached(string $pattern): ?array
    {
        $cache = self::loadCache();
        return $cache[$pattern] ?? null;
    }

    private static function updateCache(string $pattern, array $paths): void
    {
        $cache           = self::loadCache();
        $cache[$pattern] = array_values($paths);
        self::saveCache($cache);
    }

    /**
     * Scans filesystem to resolve a pattern into file paths, without caching.
     * @param string $pattern
     * @return array<string>
     */
    public static function scanPattern(string $pattern): array
    {
        // Delegate scanning to PatternScanner
        return PatternScanner::scan($pattern);
    }

    /**
     * Returns an array of file paths matching the wildcard pattern, with optional caching.
     */
    public static function getPaths(string $pattern): array
    {
        $useCache = self::$active && !is_user_logged_in();
        if ($useCache) {
            $entry = self::getCached($pattern);
            if (is_array($entry)) {
                return $entry;
            }
        }

        // Fresh scan via reusable method
        $paths = self::scanPattern($pattern);

        // Update cache if active
        if (self::$active) {
            self::updateCache($pattern, $paths);
        }

        return $paths;
    }

}
