<?php
namespace Hacon\ThemeCore\ThemeModules\ACF;

use Hacon\ThemeCore\ThemeModules\ThemeModule;

class ACF extends ThemeModule
{
    /**
     * Configuration array for ACF registration (options pages, sub pages, field groups)
     * @var array
     */
    private static array $config = [];

    /**
     * Store config on construction and include helper files
     */
    protected function __construct(array $config = [])
    {
        includePhpFiles(path_join(__DIR__, 'helpers'));
        self::$config = $config;
    }

    /**
     * Get module config
     * @return array
     */
    public static function getConfig(): array
    {
        return self::$config;
    }

    /**
     * Initialize ACF registration: options pages and field groups
     */
    public function init(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        add_action('acf/init', function () {
            foreach (self::$config['sources'] ?? [] as $src) {
                requireAll($src);
            }
        });
    }

}
