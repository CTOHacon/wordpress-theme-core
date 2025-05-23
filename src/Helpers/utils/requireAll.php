<?php
/**
 * Includes all PHP files from specified folders with customizable pattern matching.
 *
 * @param string ...$paths List of folders/files to include. The last argument can be a glob pattern.
 * @return int Number of files included
 */

use Hacon\ThemeCore\Helpers\utils\PatternScanner;
use Hacon\ThemeCore\Services\PathPatternCacheManager\PathPatternCacheManager;

function requireAll(string ...$paths): int
{
    $includedCount = 0;
    // Helper: isFilePath

    $isFilePath = static fn(string $path): bool => strpos($path, '.') !== false && strpos($path, '*') === false;

    // Helper: getRootName
    $getRootName = static fn(string $filename): string => ($dotPos = strpos($filename, '.')) !== false ? substr($filename, 0, $dotPos) : $filename;
    // Helper: sortFilesByNameStructure

    $sortFilesByNameStructure = static function (array $files) use ($getRootName): array {
        usort($files, function (string $a, string $b) use ($getRootName): int {
            $baseA = basename($a);
            $baseB = basename($b);

            $partsA = explode('.', $baseA);
            $partsB = explode('.', $baseB);

            $countA = count($partsA);
            $countB = count($partsB);

            if ($countA !== $countB) {
                return $countA <=> $countB;
            }

            foreach ($partsA as $index => $part) {
                if (!isset($partsB[$index])) {
                    return 1;
                }
                if ($part !== $partsB[$index]) {
                    return $part <=> $partsB[$index];
                }
            }

            return 0;
        });

        return $files;
    };

    foreach ($paths as $path) {
        // 1) Wildcard import
        if (strpos($path, '*') !== false) {
            if (PathPatternCacheManager::isActive()) {
                $files = PathPatternCacheManager::getPaths($path);
            } else {
                $files = PatternScanner::scan($path);
            }
            // Sort wildcard results according to hierarchy before requiring
            $files = $sortFilesByNameStructure($files);
            foreach ($files as $filePath) {
                require_once $filePath;
                $includedCount++;
            }
            continue;
        }
        // 2) Single file path
        if ($isFilePath($path)) {
            require_once get_template_directory() . "/$path";
            $includedCount++;
            continue;
        }
        // 3) Directory or glob
        $fullPath = get_template_directory() . "/$path";
        $files    = glob($fullPath) ?: [];
        if (!empty($files)) {
            $sortedFiles = $sortFilesByNameStructure($files);
            foreach ($sortedFiles as $file) {
                require_once $file;
                $includedCount++;
            }
        }
    }
    return $includedCount;
}
