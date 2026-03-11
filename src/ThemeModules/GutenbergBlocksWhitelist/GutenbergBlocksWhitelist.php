<?php
namespace Hacon\ThemeCore\ThemeModules\GutenbergBlocksWhitelist;

use Hacon\ThemeCore\ThemeModules\ThemeModule;

class GutenbergBlocksWhitelist extends ThemeModule
{
    private array $patterns;

    protected function __construct(array $config = [])
    {
        if (empty($config)) {
            throw new \Exception('GutenbergBlocksWhitelist requires at least one block pattern (e.g. "core/heading", "acf/*").');
        }

        $this->patterns = $config;
    }

    public function init()
    {
        add_filter('allowed_block_types_all', [$this, 'filterAllowedBlocks'], 10, 2);
    }

    public function filterAllowedBlocks($allowedBlocks, $editorContext)
    {
        $registry = \WP_Block_Type_Registry::get_instance();
        $allBlocks = array_keys($registry->get_all_registered());

        $allowed = [];
        foreach ($allBlocks as $blockName) {
            if ($this->matchesPatterns($blockName)) {
                $allowed[] = $blockName;
            }
        }

        return $allowed;
    }

    private function matchesPatterns(string $blockName): bool
    {
        foreach ($this->patterns as $pattern) {
            if (fnmatch($pattern, $blockName)) {
                return true;
            }
        }

        return false;
    }
}
