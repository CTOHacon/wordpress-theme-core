<?php

namespace Hacon\ThemeCore\ThemeModules\Seeders;

use Hacon\ThemeCore\ThemeModules\ThemeModule;

/**
 * Seeders Module
 * 
 * Provides data seeding functionality for themes.
 * Usage: Add ?seeders=seeder_name,another_seeder to any page URL
 * 
 * @example
 * Seeders::initModule([
 *     'header_menu' => function() { update_field(...); },
 *     'footer_menu' => function() { update_field(...); },
 * ]);
 */
class Seeders extends ThemeModule
{
    private array $seeders = [];

    protected function __construct(array $config)
    {
        $this->seeders = $config;
    }

    public function init(): void
    {
        add_action('init', [$this, 'handleSeederRequest']);
    }

    /**
     * Register a seeder dynamically after initialization
     */
    public static function register(string $name, callable $callback): void
    {
        $instance = static::getInstance();
        $instance->seeders[$name] = $callback;
    }

    /**
     * Get all registered seeders
     */
    public function all(): array
    {
        return $this->seeders;
    }

    /**
     * Check if seeder exists
     */
    public function has(string $name): bool
    {
        return isset($this->seeders[$name]);
    }

    /**
     * Run a seeder by name
     */
    public function run(string $name): bool
    {
        if (!$this->has($name)) {
            return false;
        }

        call_user_func($this->seeders[$name]);
        return true;
    }

    /**
     * Handle seeder request from URL query parameter
     */
    public function handleSeederRequest(): void
    {
        if (!isset($_GET['seeders']) || !current_user_can('manage_options')) {
            return;
        }

        $requested = array_map('trim', explode(',', sanitize_text_field($_GET['seeders'])));
        $results   = [];

        foreach ($requested as $seeder) {
            if ($this->run($seeder)) {
                $results[] = "✓ {$seeder}";
            } else {
                $results[] = "✗ {$seeder} (not found)";
            }
        }

        wp_die(
            '<h2>Seeders Executed</h2><pre>' . implode("\n", $results) . '</pre>' .
            '<h3>Available Seeders</h3><pre>' . implode("\n", array_keys($this->seeders)) . '</pre>' .
            '<p><a href="' . esc_url(remove_query_arg('seeders')) . '">← Back</a></p>',
            'Seeders'
        );
    }
}
