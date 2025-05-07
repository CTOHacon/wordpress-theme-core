<?php
namespace Hacon\ThemeCore\Helpers\utils;

/**
 * Utility for scanning file patterns (supports '**\/' recursion).
 */
class PatternScanner
{
    /**
     * Scan the theme directory for files matching the given pattern.
     * @param string $pattern Glob pattern, supports for recursive.
     * @return string[] Array of absolute file paths.
     */
    public static function scan(string $pattern): array
    {
        $baseDir = get_template_directory();
        $paths   = [];
        // Recursive pattern
        if (strpos($pattern, '**/') !== false) {
            list(
                $dirPattern,
                $filePattern
            ) = explode('**/', $pattern, 2);
            $searchDir = rtrim($baseDir . '/' . trim($dirPattern, '/'), '/');
            if (is_dir($searchDir)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($searchDir, \RecursiveDirectoryIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    if ($file->isFile() && \fnmatch($filePattern, $file->getFilename())) {
                        $paths[] = $file->getPathname();
                    }
                }
            }
        } else {
            // Simple glob
            $paths = glob($baseDir . '/' . $pattern) ?: [];
        }
        // Sort if helper is available
        if (function_exists('sortFilesByNameStructure')) {
            $paths = sortFilesByNameStructure($paths);
        }
        return $paths;
    }

}
