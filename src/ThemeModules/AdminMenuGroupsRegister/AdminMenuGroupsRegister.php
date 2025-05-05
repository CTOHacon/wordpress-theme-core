<?php
namespace Hacon\ThemeCore\ThemeModules\AdminMenuGroupsRegister;

use Hacon\ThemeCore\ThemeModules\ThemeModule;

class AdminMenuGroupsRegister extends ThemeModule
{
    /**
     * @var array List of menu group configurations
     */
    private array $groups;

    /**
     * Store menu_groups config
     */
    protected function __construct(array $config = [])
    {
        $this->groups = $config ?? [];
    }

    /**
     * Initialize menu group registration and redirection
     */
    public function init(): void
    {
        add_action('admin_menu', function () {
            global $submenu;
            foreach ($this->groups as $group) {
                $pageTitle  = $group['page_title'] ?? $group['menu_title'];
                $menuTitle  = $group['menu_title'];
                $capability = $group['capability'] ?? 'edit_theme_options';
                $menuSlug   = $group['menu_slug'];
                $iconUrl    = $group['icon_url'] ?? '';
                $position   = $group['position'] ?? null;

                // Register an empty parent page for grouping
                add_menu_page(
                    $pageTitle,
                    $menuTitle,
                    $capability,
                    $menuSlug,
                    function () {},
                    $iconUrl,
                    $position
                );

                // Redirect parent to its first child menu
                add_action("load-{$menuSlug}", function () use ($menuSlug) {
                    global $submenu;
                    if (isset($submenu[$menuSlug][0][2])) {
                        $firstChildSlug = $submenu[$menuSlug][0][2];
                        wp_safe_redirect(admin_url("admin.php?page={$firstChildSlug}"));
                        exit;
                    }
                });
            }
        }, 5);
    }

}
