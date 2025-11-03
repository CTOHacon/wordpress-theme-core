<?php
namespace Hacon\ThemeCore\ThemeModules\ThemeAssetsLoader;

use Hacon\ThemeCore\ThemeModules\ThemeModule;

/**
 * ThemeAssetsLoader provides granular methods to register and enqueue theme asset groups (CSS/JS) for different WordPress contexts.
 *
 * Usage:
 *   - Call the relevant method (e.g. enqueFrontendCSS) and pass an array of asset paths (relative to the theme root).
 *   - Each method registers/enqueues assets for its specific context (frontend, admin, block editor, inline, lazy, etc).
 *   - Use disableDefaultWPAssetsForFrontend to remove default WordPress frontend assets, with optional exclusions.
 */
class ThemeAssetsLoader extends ThemeModule
{
    private $config;

    /**
     * ThemeAssetsLoader constructor.
     * 
     * @param array $config Configuration for the assets loader
     * @throws \Exception
     */
    protected function __construct(array $config = [])
    {
        $this->config = $config;

        if (!is_array($this->config)) {
            throw new \Exception('Invalid config provided for ThemeAssetsLoader');
        }
    }

    /**
     * Initialize asset loading by processing the config array.
     * Each config key should match a method name; the value is passed as arguments.
     * If the method has one argument, the value is passed as is.
     * If the method has multiple arguments, the value is unpacked as arguments.
     */
    public function init()
    {
        foreach ($this->config as $method => $value) {
            if (
                method_exists($this, $method) && is_callable([
                    $this,
                    $method
                ])
            ) {
                $ref    = new \ReflectionMethod($this, $method);
                $params = $ref->getParameters();
                if (count($params) === 1) {
                    $this->$method($value);
                } elseif (is_array($value)) {
                    $this->$method(...$value);
                } else {
                    // If not an array but multiple params expected, wrap in array
                    $this->$method($value);
                }
            }
        }
    }

    /**
     * Removes all default WordPress frontend CSS/JS assets except those matching any pattern in $exclude.
     * Useful for full theme control over loaded assets.
     *
     * @param array $exclude Array of asset handle patterns to keep (e.g. ['my-handle*', 'jquery'])
     */
    public function disableDefaultWPAssetsForFrontend(array $exclude = [])
    {
        if (is_user_logged_in()) {
            return;
        }
        if (!is_admin()) {
            global $wp_styles;
            $deque_styles = [];
            // $wp_styles may be null in some contexts (causes "Attempt to read property 'queue' on null").
            // Only iterate if the object exists and has a 'queue' property.
            if (isset($wp_styles) && is_object($wp_styles) && property_exists($wp_styles, 'queue') && is_array($wp_styles->queue)) {
                foreach ($wp_styles->queue as $handle) {
                    $skip = false;
                    foreach ($exclude as $pattern) {
                        if (fnmatch($pattern, $handle)) {
                            $skip = true;
                            break;
                        }
                    }
                    if ($skip)
                        continue;
                    $deque_styles[] = $handle;
                }
            }
            foreach ($deque_styles as $handle) {
                wp_dequeue_style($handle);
            }
            global $wp_scripts;
            if (isset($wp_scripts) && is_object($wp_scripts) && property_exists($wp_scripts, 'queue')) {
                $deque_scripts = [];
                foreach ($wp_scripts->queue as $handle) {
                    $skip = false;
                    foreach ($exclude as $pattern) {
                        if (fnmatch($pattern, $handle)) {
                            $skip = true;
                            break;
                        }
                    }
                    if ($skip)
                        continue;
                    $deque_scripts[] = $handle;
                }
                foreach ($deque_scripts as $handle) {
                    wp_dequeue_script($handle);
                }
            }
            remove_action('wp_print_styles', 'print_emoji_styles');
        }
    }

    /**
     * Enqueue CSS files for the public frontend (outside admin and block editor).
     *
     * @param array $paths Array of CSS file paths relative to the theme root
     */
    public function enqueFrontendCSS(array $paths)
    {
        add_action('wp_enqueue_scripts', function () use ($paths) {
            foreach ($paths as $cssAsset) {
                wp_enqueue_style("wp_theme-$cssAsset", getThemeFileUri($cssAsset));
            }
        });
    }

    /**
     * Enqueue CSS files for the WordPress admin area only.
     *
     * @param array $paths Array of CSS file paths relative to the theme root
     */
    public function enqueAdminCSS(array $paths)
    {
        add_action('admin_enqueue_scripts', function () use ($paths) {
            foreach ($paths as $cssAsset) {
                wp_enqueue_style("wp_theme-$cssAsset", getThemeFileUri($cssAsset));
            }
        });
    }

    /**
     * Enqueue CSS files for the block editor (Gutenberg) in both frontend and backend.
     *
     * @param array $paths Array of CSS file paths relative to the theme root
     */
    public function enqueBlockCSS(array $paths)
    {
        add_action('enqueue_block_assets', function () use ($paths) {
            foreach ($paths as $cssAsset) {
                wp_enqueue_style("wp_theme-$cssAsset", getThemeFileUri($cssAsset));
            }
        });
    }

    /**
     * Enqueue CSS files for the Gutenberg editor in all contexts (editor frame, pattern edit, preview).
     *
     * @param array $paths Array of CSS file paths relative to the theme root
     */
    public function enqueEditorCSS(array $paths)
    {
        // Editor assets in the backend post/block editor
        add_action('enqueue_block_editor_assets', function () use ($paths) {
            foreach ($paths as $cssAsset) {
                wp_enqueue_style("wp_theme-editor-{$cssAsset}", getThemeFileUri($cssAsset));
            }
        });
    }

    /**
     * Enqueue JS files for the public frontend (outside admin and block editor).
     *
     * @param array $paths Array of JS file paths relative to the theme root
     */
    public function enqueFrontendJS(array $paths)
    {
        add_action('wp_enqueue_scripts', function () use ($paths) {
            foreach ($paths as $jsAsset) {
                wp_enqueue_script("wp_theme-$jsAsset", getThemeFileUri($jsAsset), [], null, true);
            }
        });
    }

    /**
     * Enqueue JS files for the WordPress admin area only.
     *
     * @param array $paths Array of JS file paths relative to the theme root
     */
    public function enqueAdminJS(array $paths)
    {
        add_action('admin_enqueue_scripts', function () use ($paths) {
            foreach ($paths as $jsAsset) {
                wp_enqueue_script("wp_theme-$jsAsset", getThemeFileUri($jsAsset), [], null, true);
            }
        });
    }

    /**
     * Enqueue JS files for the block editor (Gutenberg) in both frontend and backend.
     *
     * @param array $paths Array of JS file paths relative to the theme root
     */
    public function enqueBlockJS(array $paths)
    {
        add_action('enqueue_block_assets', function () use ($paths) {
            foreach ($paths as $jsAsset) {
                wp_enqueue_script("wp_theme-$jsAsset", getThemeFileUri($jsAsset), [], null, true);
            }
        });
    }

    /**
     * Output inline CSS in the <head> for the public frontend.
     *
     * @param array $paths Array of CSS file paths relative to the theme root
     */
    public function enqueHeadInlineCSS(array $paths)
    {
        add_action('wp_head', function () use ($paths) {
            foreach ($paths as $cssFile) {
                $cssFilePath = getThemeFilePath($cssFile);
                if (file_exists($cssFilePath)) {
                    echo '<style>';
                    echo file_get_contents($cssFilePath);
                    echo '</style>';
                }
            }
        });
    }

    /**
     * Output inline CSS in the <footer> for the public frontend.
     *
     * @param array $paths Array of CSS file paths relative to the theme root
     */
    public function enqueFooterInlineCSS(array $paths)
    {
        add_action('wp_footer', function () use ($paths) {
            foreach ($paths as $cssFile) {
                $cssFilePath = getThemeFilePath($cssFile);
                if (file_exists($cssFilePath)) {
                    echo '<style>';
                    echo file_get_contents($cssFilePath);
                    echo '</style>';
                }
            }
        });
    }

    /**
     * Output inline JS in the <head> for the public frontend.
     *
     * @param array $paths Array of JS file paths relative to the theme root
     */
    public function enqueHeadInlineJS(array $paths)
    {
        add_action('wp_head', function () use ($paths) {
            foreach ($paths as $jsFile) {
                $jsFilePath = getThemeFilePath($jsFile);
                if (file_exists($jsFilePath)) {
                    echo '<script type="module">';
                    echo file_get_contents($jsFilePath);
                    echo '</script>';
                }
            }
        });
    }

    /**
     * Output inline JS in the <footer> for the public frontend.
     *
     * @param array $paths Array of JS file paths relative to the theme root
     */
    public function enqueFooterInlineJS(array $paths)
    {
        add_action('wp_footer', function () use ($paths) {
            foreach ($paths as $jsFile) {
                $jsFilePath = getThemeFilePath($jsFile);
                if (file_exists($jsFilePath)) {
                    echo '<script type="module">';
                    echo file_get_contents($jsFilePath);
                    echo '</script>';
                }
            }
        }, 5);
    }

    /**
     * Output inline JS for the block editor (Gutenberg) in both frontend and backend.
     *
     * @param array $paths Array of JS file paths relative to the theme root
     */
    public function enqueBlockJSInline(array $paths)
    {
        add_action('enqueue_block_assets', function () use ($paths) {
            foreach ($paths as $jsFile) {
                $jsFilePath = getThemeFilePath($jsFile);
                if (file_exists($jsFilePath)) {
                    echo '<script type="module">';
                    echo file_get_contents($jsFilePath);
                    echo '</script>';
                }
            }
        });
    }

    /**
     * Enqueue JS files to be lazy-loaded on the frontend (loaded after user interaction).
     *
     * @param array $paths Array of JS file paths relative to the theme root
     */
    public function enqueLazyLoadedJS(array $paths)
    {
        add_action('wp_footer', function () use ($paths) {
            ?>
            <script>
                var loaded = false;

                function loadLazyScripts(srcList)
                {
                    if (loaded) return;
                    srcList.forEach(function (src)
                    {
                        var script = document.createElement('script');
                        script.src = src;
                        script.type = 'module';
                        script.defer = true;
                        document.body.appendChild(script);
                    });
                    loaded = true;
                }
                ["click", "scroll", "keypress", "mousemove", "touchmove", "touchstart"].forEach(function (event)
                {
                    window.addEventListener(event, function ()
                    {
                        loadLazyScripts([
                            <?php
                            foreach ($paths as $script) {
                                $sourceBuildFileUri = getThemeFileUri($script);
                                if ($sourceBuildFileUri) {
                                    echo "'{$sourceBuildFileUri}',";
                                } else {
                                    echo "'{$script}',";
                                }
                            }
                            ?>
                        ]);
                    }, {
                        once: true
                    });
                });
            </script>
            <?php
        });
    }

}