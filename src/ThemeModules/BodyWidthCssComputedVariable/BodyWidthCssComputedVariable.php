<?php
namespace Hacon\ThemeCore\ThemeModules\BodyWidthCssComputedVariable;

use Hacon\ThemeCore\ThemeModules\ThemeModule;

class BodyWidthCssComputedVariable extends ThemeModule
{
    private string $cssVariableName;

    protected function __construct(array $config)
    {
        $this->cssVariableName = $config['cssVariableName'] ?? 'body-width';
    }

    public function getFrontendScripts()
    {
        ob_start();
        ?>
        <script>(function ()
            {
                var setVar = function ()
                {
                    if (document.body)
                    {
                        document.documentElement.style.setProperty('--<?= $this->cssVariableName ?>', document.body.clientWidth + 'px');
                    }
                };
                if (document.body)
                {
                    setVar();
                } else
                {
                    var observer = new MutationObserver(function ()
                    {
                        if (document.body)
                        {
                            setVar();
                            observer.disconnect();
                        }
                    });
                    observer.observe(document.documentElement, { childList: true, subtree: true });
                }
                window.addEventListener('resize', setVar, { passive: true });
            })();</script>
        <?php
        return ob_get_clean();
    }

    public function init(): void
    {
        add_action('wp_head', function () {
            echo self::getFrontendScripts();
        }, 1);
    }

}