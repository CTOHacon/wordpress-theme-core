<?php
namespace Hacon\ThemeCore\ThemeModules\CustomArchivePages;

use Hacon\ThemeCore\ThemeModules\ThemeModule;

class CustomArchivePages extends ThemeModule
{
    /**
     * @var array
     */
    private $postTypes = [];

    protected function __construct(array $config = [])
    {
        $this->postTypes = isset($config['postTypes']) && is_array($config['postTypes'])
            ? $config['postTypes']
            : [];
    }

    public function init()
    {
        $this->registerSEOIntegration();
        $this->registerPolylangIntegration();
        $this->registerArchivePagesCPT();
        $this->registerCustomArchiveSupport();
        $this->registerAdminMenu();
        $this->registerQueryOverride();
        $this->registerTemplateOverride();
        $this->registerArchivePageDisplay();
    }

    /**
     * Integrate with Yoast SEO to show meta box on archive_pages
     */
    private function registerSEOIntegration()
    {
        // Only if Yoast SEO plugin is active
        add_action('init', function () {
            if (!defined('WPSEO_VERSION')) {
                return;
            }
            // Ensure archive_pages is not excluded from Yoast meta box
            add_filter('wpseo_exclude_posttype', function ($exclude, $post_type) {
                if ('archive_pages' === $post_type) {
                    return false;
                }
                return $exclude;
            }, 10, 2);
            // Optionally include archive_pages in sitemap
            add_filter('wpseo_sitemap_exclude_post_type', function ($exclude, $post_type) {
                if ('archive_pages' === $post_type) {
                    return false;
                }
                return $exclude;
            }, 10, 2);
        });
    }

    /**
     * Integrate with Polylang to make archive_pages translatable
     */
    private function registerPolylangIntegration()
    {
        // Register archive_pages as a translatable post type
        add_filter('pll_post_types', function (array $post_types) {
            $post_types[] = 'archive_pages';
            return array_unique($post_types);
        });
    }

    private function registerArchivePagesCPT()
    {
        // Register early so Polylang can detect our CPT
        add_action('init', function () {
            register_post_type('archive_pages', [
                // Make CPT public for plugins (Yoast, Polylang) but hide front-end permalinks
                'public'              => true,
                'publicly_queryable'  => true,  // enable for plugin integrations
                'show_ui'             => true,
                'show_in_menu'        => false,
                'supports'            => [
                    'title',
                    'thumbnail',
                    'editor'
                ],
                'exclude_from_search' => true,
                'rewrite'             => false, // no pretty permalinks
                'query_var'           => false, // disable query var
                'has_archive'         => false,
                'label'               => 'Archive Pages',
                'show_in_rest'        => true, // Enable REST API and Gutenberg
                'rest_base'           => 'archive_pages',
                'taxonomies'          => ['language'], // Polylang taxonomy
                'show_in_nav_menus'   => false,
                'show_in_admin_bar'   => false,
            ]);
        }, 0);
    }

    private function hasCustomArchivePage(string $post_type): bool
    {
        $types = get_option('custom_archive_page_types', []);
        return in_array($post_type, (array) $types, true);
    }

    private function registerCustomArchiveSupport()
    {
        add_action('init', function () {
            foreach ($this->postTypes as $pt) {
                $types = get_option('custom_archive_page_types', []);
                if (!in_array($pt, $types, true)) {
                    $types[] = $pt;
                    update_option('custom_archive_page_types', $types);
                }
            }
        });
    }

    private function registerAdminMenu()
    {
        add_action('admin_menu', function () {
            foreach (get_post_types(['public' => true], 'names') as $pt) {
                if (!$this->hasCustomArchivePage($pt)) {
                    continue;
                }
                $pt_obj = get_post_type_object($pt);
                // Use CPT archive rewrite slug or fallback to archive-<post_type>
                $slug = (!empty($pt_obj->rewrite['slug'])) ? $pt_obj->rewrite['slug'] : 'archive-' . $pt;
                // Add submenu and capture hook suffix for load action
                $hook = add_submenu_page(
                    'edit.php?post_type=' . $pt,
                    "Edit {$pt_obj->labels->name} Archive",
                    'Edit Archive Page',
                    'edit_posts',
                    "edit-{$pt}-archive",
                    function () {
                        // no output, redirect handled on load-{$hook}
                    }
                );
                // On page load, create archive page if needed and redirect
                add_action("load-{$hook}", function () use ($pt, $pt_obj) {
                    // Use CPT archive rewrite slug or fallback to archive-<post_type>
                    $slug = (!empty($pt_obj->rewrite['slug'])) ? $pt_obj->rewrite['slug'] : 'archive-' . $pt;
                    $page = get_page_by_path($slug, OBJECT, 'archive_pages');
                    if (!$page) {
                        // Create the archive page
                        $page_id = wp_insert_post([
                            'post_title'   => ucfirst($pt) . ' Archive',
                            'post_name'    => $slug,
                            'post_type'    => 'archive_pages',
                            'post_status'  => 'publish',
                            'post_content' => '',
                        ]);
                        // Assign current language if Polylang is active
                        if (function_exists('pll_set_post_language')) {
                            pll_set_post_language($page_id, pll_current_language());
                        }
                    } else {
                        $page_id = $page->ID;
                    }
                    // If a translation exists for current language, use it
                    if (function_exists('pll_get_post')) {
                        $translated = pll_get_post($page_id);
                        if ($translated) {
                            $page_id = $translated;
                        }
                    }
                    wp_safe_redirect(admin_url("post.php?post={$page_id}&action=edit"));
                    exit;
                });
            }
        });
    }

    private function registerQueryOverride()
    {
        add_action('pre_get_posts', function (\WP_Query $query) {
            if (is_admin() || !$query->is_main_query()) {
                return;
            }
            foreach ($this->postTypes as $pt) {
                if ($query->is_post_type_archive($pt)) {
                    // Handle pagination beyond available posts as 404
                    $paged = max(1, get_query_var('paged'));
                    $ppp   = absint($query->get('posts_per_page')) ?: get_option('posts_per_page');
                    $total = wp_count_posts($pt)->publish;
                    $max   = $ppp > 0 ? ceil($total / $ppp) : 0;
                    if ($paged > 1 && ($max < 1 || $paged > $max)) {
                        // Trigger 404
                        $query->set_404();
                        status_header(404);
                        nocache_headers();
                        return;
                    }
                    $pt_obj = get_post_type_object($pt);
                    // Use CPT archive rewrite slug or fallback to archive-<post_type>
                    $slug = (!empty($pt_obj->rewrite['slug'])) ? $pt_obj->rewrite['slug'] : 'archive-' . $pt;
                    $page = get_page_by_path($slug, OBJECT, 'archive_pages');
                    if ($page) {
                        $page_id = $page->ID;
                        // If Polylang is active, get translation for current language
                        if (function_exists('pll_get_post')) {
                            $translated = pll_get_post($page_id);
                            if ($translated) {
                                $page_id = $translated;
                            }
                        }
                        // Load the archive_pages page instead of CPT archive
                        $query->set('post_type', 'archive_pages');
                        $query->set('page_id', $page_id);
                        // Adjust flags
                        $query->is_singular          = true;
                        $query->is_page              = true;
                        $query->is_archive           = true;
                        $query->is_post_type_archive = true;
                        $query->is_home              = false;

                        $query->queried_object    = get_post($page_id);
                        $query->queried_object_id = $page_id;
                    }
                    break;
                }
            }
        }, 1);
    }

    private function registerTemplateOverride()
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

    /**
     * Render the archive page content (page content + CPT loop)
     */
    private function registerArchivePageDisplay()
    {
        add_filter('the_content', function ($content) {
            if (is_singular('archive_pages')) {
                $page = get_queried_object();
                // Determine related CPT based on page slug
                $cpt = null;
                foreach ($this->postTypes as $pt) {
                    $pt_obj = get_post_type_object($pt);
                    $slug   = !empty($pt_obj->rewrite['slug'])
                        ? $pt_obj->rewrite['slug']
                        : 'archive-' . $pt;
                    if ($page->post_name === $slug) {
                        $cpt = $pt;
                        break;
                    }
                }
                if ($cpt) {
                    // original page content
                    $output = $content;
                    // CPT loop for "$cpt"
                    $paged = get_query_var('paged') ?: 1;
                    $loop  = new \WP_Query([
                        'post_type' => $cpt,
                        'paged'     => $paged,
                    ]);
                    if ($loop->have_posts()) {
                        ob_start();
                        while ($loop->have_posts()) {
                            $loop->the_post();
                            get_template_part('template-parts/content', $cpt);
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
