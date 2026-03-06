<?php

use Hacon\ThemeCore\Services\TemplatingService\ComponentRenderService;

if (!function_exists('component')) {
    /**
     * @deprecated Use render_component_template() instead
     */
    function component(string $component, array $htmlAttributes = [], array $props = [])
    {
        $processor = new ComponentRenderService($component, $htmlAttributes, $props);
        $processor->render();
    }

}

function render_component_template(string $component, array $htmlAttributes = [], array $props = [])
{
    $processor = new ComponentRenderService($component, $htmlAttributes, $props);
    $processor->render();
}