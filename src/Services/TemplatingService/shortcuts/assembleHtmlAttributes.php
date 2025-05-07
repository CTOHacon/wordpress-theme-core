<?php

use Hacon\ThemeCore\Services\TemplatingService\HtmlAttributesService;

/**
 * Собирает HTML-атрибуты из нескольких массивов.
 *
 * @param array ...$attributeArrays Массивы атрибутов
 * @return string Строка HTML-атрибутов
 */
/**
 * Shorthand for HtmlAttributesService::assemble()
 */
function assembleHtmlAttributes(...$attributeArrays): string
{
    return HtmlAttributesService::assemble(...$attributeArrays);
}