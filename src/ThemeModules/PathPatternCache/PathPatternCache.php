<?php
namespace Hacon\ThemeCore\ThemeModules\PathPatternCache;

use Hacon\ThemeCore\ThemeModules\ThemeModule;
use Hacon\ThemeCore\Services\PathPatternCacheManager\PathPatternCacheManager;

class PathPatternCache extends ThemeModule
{
    /**
     * Prevent direct instantiation
     */
    protected function __construct(array $config = [])
    {
    }

    /**
     * Activate pattern cache service
     */
    public function init(): void
    {
        PathPatternCacheManager::activate();
    }

}
