<?php
namespace Hacon\ThemeCore\Services\CustomArchivePages;

/**
 * Service for enabling custom archive pages for CPTs.
 * Usage: enableCustomArchivePage($postType);
 */
class CustomArchivePagesService
{
    /**
     * Enable custom archive page for a post type.
     *
     * @param string $postType
     */
    public static function enable(string $postType): void
    {
        // Register the archive_pages CPT (once)
        add_action('init', [
            self::class,
            'registerArchivePagesCPT'
        ], 0);
        // Mark this post type as having a custom archive page
        add_action('init', function () use ($postType) {
            $types = get_option('custom_archive_page_types', []);
            if (!in_array($postType, $types, true)) {
                $types[] = $postType;
                update_option('custom_archive_page_types', $types);
            }
        });
        // Register all hooks for this post type
        add_action('init', function () use ($postType) {
            self::registerHooks($postType);
        }, 20);
    }

    /**
     * Register the archive_pages CPT.
     */
    public static function registerArchivePagesCPT(): void
    {
        if (post_type_exists('archive_pages'))
            return;
        register_post_type('archive_pages', [
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'supports'            => [
                'title',
                'thumbnail',
                'editor'
            ],
            'exclude_from_search' => true,
            'rewrite'             => false,
            'query_var'           => false,
            'has_archive'         => false,
            'label'               => 'Archive Pages',
            'show_in_rest'        => true,
            'rest_base'           => 'archive_pages',
            'taxonomies'          => ['language'],
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => false,
        ]);
    }

    /**
     * Register all hooks for a given post type.
     */
    public static function registerHooks(string $postType): void
    {
        self::registerSEOIntegration();
        self::registerPolylangIntegration();
        self::registerAdminMenu($postType);
        self::registerQueryOverride($postType);
        self::registerTemplateOverride();
        self::registerArchivePageDisplay($postType);
    }

    private static function registerSEOIntegration(): void
    {
        add_action('init', function () {
            if (!defined('WPSEO_VERSION'))
                return;
            add_filter('wpseo_exclude_posttype', function ($exclude, $post_type) {
                if ('archive_pages' === $post_type)
                    return false;
                return $exclude;
            }, 10, 2);
            add_filter('wpseo_sitemap_exclude_post_type', function ($exclude, $post_type) {
                if ('archive_pages' === $post_type)
                    return false;
                return $exclude;
            }, 10, 2);
        });
    }

    private static function registerPolylangIntegration(): void
    {
        add_filter('pll_post_types', function (array $post_types) {
            $post_types[] = 'archive_pages';
            return array_unique($post_types);
        });
    }

    private static function hasCustomArchivePage(string $postType): bool
    {
        $types = get_option('custom_archive_page_types', []);
        return in_array($postType, (array) $types, true);
    }

    private static function registerAdminMenu(string $postType): void
    {
        add_action('admin_menu', function () use ($postType) {
            if (!self::hasCustomArchivePage($postType))
                return;
            $pt_obj = get_post_type_object($postType);
            $slug   = (!empty($pt_obj->rewrite['slug'])) ? $pt_obj->rewrite['slug'] : 'archive-' . $postType;
            $hook   = add_submenu_page(
                'edit.php?post_type=' . $postType,
                "Edit {$pt_obj->labels->name} Archive",
                'Edit Archive Page',
                'edit_posts',
                "edit-{$postType}-archive",
                function () {}
            );
            add_action("load-{$hook}", function () use ($postType, $pt_obj, $slug) {
                $page = get_page_by_path($slug, OBJECT, 'archive_pages');
                if (!$page) {
                    $page_id = wp_insert_post([
                        'post_title'   => ucfirst($postType) . ' Archive',
                        'post_name'    => $slug,
                        'post_type'    => 'archive_pages',
                        'post_status'  => 'publish',
                        'post_content' => '',
                    ]);
                    if (function_exists('pll_set_post_language')) {
                        pll_set_post_language($page_id, pll_current_language());
                    }
                } else {
                    $page_id = $page->ID;
                }
                if (function_exists('pll_get_post')) {
                    $translated = pll_get_post($page_id);
                    if ($translated) {
                        $page_id = $translated;
                    }
                }
                wp_safe_redirect(admin_url("post.php?post={$page_id}&action=edit"));
                exit;
            });
        });
    }

    private static function registerQueryOverride(string $postType): void
    {
        add_action('pre_get_posts', function (\WP_Query $query) use ($postType) {
            if (is_admin() || !$query->is_main_query())
                return;
            if ($query->is_post_type_archive($postType)) {
                $paged = max(1, get_query_var('paged'));
                $ppp   = absint($query->get('posts_per_page')) ?: get_option('posts_per_page');
                $total = wp_count_posts($postType)->publish;
                $max   = $ppp > 0 ? ceil($total / $ppp) : 0;
                if ($paged > 1 && ($max < 1 || $paged > $max)) {
                    $query->set_404();
                    status_header(404);
                    nocache_headers();
                    return;
                }
                $pt_obj = get_post_type_object($postType);
                $slug   = (!empty($pt_obj->rewrite['slug'])) ? $pt_obj->rewrite['slug'] : 'archive-' . $postType;
                $page   = get_page_by_path($slug, OBJECT, 'archive_pages');
                if ($page) {
                    $page_id = $page->ID;
                    if (function_exists('pll_get_post')) {
                        $translated = pll_get_post($page_id);
                        if ($translated) {
                            $page_id = $translated;
                        }
                    }
                    $query->set('post_type', 'archive_pages');
                    $query->set('page_id', $page_id);
                    $query->is_singular          = true;
                    $query->is_page              = true;
                    $query->is_archive           = true;
                    $query->is_post_type_archive = true;
                    $query->is_home              = false;
                    $query->queried_object       = get_post($page_id);
                    $query->queried_object_id    = $page_id;
                }
            }
        }, 1);
    }

    private static function registerTemplateOverride(): void
    {
        add_filter('template_include', function ($template) {
            if (is_singular('archive_pages')) {
                return locate_template([
                    'page.php',
                    'singular.php',
                    'index.php'
                ]);
            }
            return $template;
        }, 20);
    }

    private static function registerArchivePageDisplay(string $postType): void
    {
        add_filter('the_content', function ($content) use ($postType) {
            if (is_singular('archive_pages')) {
                $page   = get_queried_object();
                $pt_obj = get_post_type_object($postType);
                $slug   = !empty($pt_obj->rewrite['slug']) ? $pt_obj->rewrite['slug'] : 'archive-' . $postType;
                if ($page->post_name === $slug) {
                    $output = $content;
                    $paged  = get_query_var('paged') ?: 1;
                    $loop   = new \WP_Query([
                        'post_type' => $postType,
                        'paged'     => $paged,
                    ]);
                    if ($loop->have_posts()) {
                        ob_start();
                        while ($loop->have_posts()) {
                            $loop->the_post();
                            get_template_part('template-parts/content', $postType);
                        }
                        echo paginate_links(['total' => $loop->max_num_pages]);
                        $output .= ob_get_clean();
                        wp_reset_postdata();
                    }
                    return $output;
                }
            }
            return $content;
        }, 20);
    }

}
