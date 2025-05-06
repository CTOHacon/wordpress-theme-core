<?php
namespace Hacon\ThemeCore\ThemeModules\DocumentScrollbarWidthCssVariable;

use Hacon\ThemeCore\ThemeModules\ThemeModule;

class DocumentScrollbarWidthCssVariable extends ThemeModule
{
    public function getFrontendScripts()
    {
        ob_start();
        ?>
        <script>
            function setScrollbarWidth()
            {
                document.documentElement.style.setProperty('--scrollbar-width', (window.innerWidth - document.documentElement.offsetWidth) + 'px');
            }
            // Set immediately on script load
            setScrollbarWidth();
            // Also set as soon as DOM is ready (in case of layout shifts)
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', setScrollbarWidth);
            } else {
                setScrollbarWidth();
            }
            window.addEventListener('resize', setScrollbarWidth);
        </script>
        <?php
        return ob_get_clean();
    }

    public function init(): void
    {
        add_action('wp_footer', function () {
            echo self::getFrontendScripts();
        }, 1);
    }

}