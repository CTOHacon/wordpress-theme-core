<?php
namespace Hacon\ThemeCore\Services\TemplatingService;

use Closure;

/**
 * Handles the processing and rendering of components.
 */
class ComponentRenderService
{
    private string $componentName;
    private string $componentTemplatePath;
    private array $htmlAttributes;
    private array $props;

    private string $componentDomain = 'default';

    private static array $domains = [
        'default' => 'source/components',
    ];

    public static function defineDomain(string $domain, string $base): void
    {
        self::$domains[$domain] = $base;
    }

    /**
     * @deprecated Use fromName() or fromPath() instead
     */
    public function __construct(string $componentRef = '', array $htmlAttributes = [], array $props = [])
    {
        if ($componentRef === '') {
            return; // Internal use by named constructors
        }

        if (str_ends_with($componentRef, '.php')) {
            $this->componentName = basename($componentRef, '.php');
            $this->componentTemplatePath = get_template_directory() . '/' . ltrim($componentRef, '/');
        } else {
            if (strpos($componentRef, '.')) {
                [$domain, $componentRef] = explode('.', $componentRef);
                $this->componentDomain = $domain;
            }
            $this->componentName = $componentRef;
            $this->componentTemplatePath = $this->resolveTemplatePath($componentRef);
        }

        $this->validateTemplatePath($componentRef);
        $this->htmlAttributes = $htmlAttributes;
        $this->props          = $props;
    }

    /**
     * Create from component name with auto-discovery via glob
     */
    public static function fromName(string $componentName, array $htmlAttributes = [], array $props = []): self
    {
        $instance = new self();
        $instance->componentName = $componentName;

        if (strpos($componentName, '.')) {
            [$domain, $componentName] = explode('.', $componentName);
            $instance->componentDomain = $domain;
            $instance->componentName = $componentName;
        }

        $instance->componentTemplatePath = $instance->resolveTemplatePath($componentName);

        $instance->validateTemplatePath($componentName);
        $instance->htmlAttributes = $htmlAttributes;
        $instance->props          = $props;

        return $instance;
    }

    /**
     * Create from explicit name and template path
     */
    public static function fromPath(string $componentName, string $componentTemplatePath, array $htmlAttributes = [], array $props = []): self
    {
        $instance = new self();
        $instance->componentName = $componentName;

        $instance->componentTemplatePath = get_template_directory() . '/' . ltrim($componentTemplatePath, '/');

        $instance->validateTemplatePath($componentName);
        $instance->htmlAttributes = $htmlAttributes;
        $instance->props          = $props;

        return $instance;
    }

    private function resolveTemplatePath(string $componentName): string
    {
        $pattern = self::$domains[$this->componentDomain] . '/**/' . $componentName . '.php';
        return getThemeFilePath($pattern) ?: '';
    }

    private function validateTemplatePath(string $ref): void
    {
        if (!$this->componentTemplatePath || !file_exists($this->componentTemplatePath)) {
            throw new \RuntimeException("Component file '{$ref}' not found");
        }
    }

    private function processProps(): array
    {
        $processedProps = $this->props;

        foreach ($processedProps as $key => $value) {
            if ($value instanceof Closure) {
                ob_start();
                call_user_func($value);
                $processedProps[$key] = ob_get_clean();
            }
        }

        return $processedProps;
    }

    public function render(): void
    {
        $props          = $this->processProps();
        $htmlAttributes = $this->htmlAttributes;
        $componentName  = $this->componentName;

        $htmlAttributes['data-component'] = $componentName;

        extract($props);

        $htmlAttributesString = function (...$attributes) use ($htmlAttributes) {
            return assembleHtmlAttributes($htmlAttributes, ...$attributes);
        };

        include $this->componentTemplatePath;
    }

}
