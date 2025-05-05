<?php
namespace Hacon\ThemeCore\ThemeModules\InternalNavigationPrefetch;

use Hacon\ThemeCore\ThemeModules\ThemeModule;

class InternalNavigationPrefetch extends ThemeModule
{
    protected function __construct(array $config = [])
    {
        // No custom config for now
    }

    public function init(): void
    {
        // Inject prefetch script in footer
        add_action('wp_footer', function () {
            echo $this->getPrefetchScript();
        }, 5);
    }

    /**
     * Generate inline JavaScript for prefetching internal navigation links
     *
     * @return string
     */
    protected function getPrefetchScript(): string
    {
        ob_start();
        ?>
        <script>
            (function ()
            {
                // Check for prefetch support
                var linkTest = document.createElement('link');
                var supported = linkTest.relList && linkTest.relList.supports && linkTest.relList.supports('prefetch');
                if (!supported) return;

                var cache = new Set();

                function prefetch(url)
                {
                    if (cache.has(url)) return;
                    cache.add(url);
                    var link = document.createElement('link');
                    link.rel = 'prefetch';
                    link.href = url;
                    document.head.appendChild(link);
                }

                function handleEvent(e)
                {
                    var el = e.target.closest('a');
                    if (!el || !el.href) return;
                    var url = el.href;
                    if (url.indexOf(location.origin) !== 0) return;
                    if (url.indexOf('#') !== -1) return;
                    prefetch(url);
                }

                document.addEventListener('mouseover', handleEvent, { passive: true });
                document.addEventListener('touchstart', handleEvent, { passive: true });
            })();
        </script>
        <?php
        return ob_get_clean();
    }

}
