<?php
namespace Hacon\ThemeCore\ThemeModules\FormOrdersPostType;

use Hacon\ThemeCore\ThemeModules\ThemeModule;

class FormOrdersPostType extends ThemeModule
{
    public function init()
    {
        add_action('init', [
            $this,
            'registerPostType'
        ]);
        add_action('admin_menu', [
            $this,
            'addExportSubmenu'
        ]);
        // Handle export form submission via admin-post
        add_action('admin_post_export_form_orders', [
            $this,
            'handleExport'
        ]);
    }

    public function registerPostType()
    {
        register_post_type('form-orders', [
            'labels'              => [
                'name'               => 'Form Orders',
                'singular_name'      => 'Form Order',
                'add_new'            => 'Add New',
                'add_new_item'       => 'Add New Form Order',
                'edit'               => 'Edit',
                'edit_item'          => 'Edit Form Order',
                'new_item'           => 'New Form Order',
                'view'               => 'View Form Order',
                'view_item'          => 'View Form Order',
                'search_items'       => 'Search Form Order',
                'not_found'          => 'No Form Order found',
                'not_found_in_trash' => 'No Form Order found in Trash',
                'parent'             => 'Parent Form Order'
            ],
            'menu_icon'           => 'dashicons-cart',
            'supports'            => [
                'title',
                'editor'
            ],
            'public'              => false,
            'show_ui'             => true,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'show_in_nav_menus'   => false,
            'has_archive'         => false,
            'show_in_rest'        => false,
        ]);
    }

    public function addExportSubmenu()
    {
        add_submenu_page(
            'edit.php?post_type=form-orders',
            'Export Form Orders',
            'Export',
            'manage_options',
            'export-form-orders',
            [
                $this,
                'renderExportPage'
            ]
        );
    }

    public function renderExportPage()
    {
        ?>
        <div class="wrap">
            <h1>Export Form Orders</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('export_form_orders_action'); ?>
                <input type="hidden" name="action" value="export_form_orders">
                <label for="export_format">Format:</label>
                <select name="export_format" id="export_format">
                    <option value="csv">CSV</option>
                    <option value="xls">XLS</option>
                </select>
                <label for="export_period" style="margin-left:20px;">Period:</label>
                <select name="export_period" id="export_period">
                    <option value="all">All Time</option>
                    <option value="last_week">Last Week</option>
                    <option value="last_month">Last Month</option>
                    <option value="last_2_months">Last 2 Months</option>
                    <option value="last_year">Last Year</option>
                </select>
                <?php submit_button('Export Form Orders'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handles export via admin-post action
     */
    public function handleExport()
    {
        // Clean any output buffer to allow fresh headers
        while (ob_get_level()) {
            ob_end_clean();
        }
        nocache_headers();
        // Verify nonce
        check_admin_referer('export_form_orders_action');
        $format = sanitize_text_field($_REQUEST['export_format'] ?? 'csv');
        $period = sanitize_text_field($_REQUEST['export_period'] ?? 'all');
        $this->exportOrders($format, $period);
    }

    private function exportOrders($format, $period = 'all')
    {
        $args = [
            'post_type'      => 'form-orders',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];
        // Date filtering
        if ($period !== 'all') {
            $date_query = [];
            $now        = current_time('timestamp');
            switch ($period) {
                case 'last_week':
                    $date_query['after'] = date('Y-m-d', strtotime('-1 week', $now));
                    break;
                case 'last_month':
                    $date_query['after'] = date('Y-m-d', strtotime('-1 month', $now));
                    break;
                case 'last_2_months':
                    $date_query['after'] = date('Y-m-d', strtotime('-2 months', $now));
                    break;
                case 'last_year':
                    $date_query['after'] = date('Y-m-d', strtotime('-1 year', $now));
                    break;
            }
            if (!empty($date_query)) {
                $args['date_query'][] = $date_query;
            }
        }
        $posts  = get_posts($args);
        $rows   = [];
        $rows[] = [
            'Title',
            'Date',
            'Content'
        ];
        foreach ($posts as $post) {
            $rows[] = [
                $post->post_title,
                get_the_date('Y-m-d H:i', $post),
                $this->formatContent($post->post_content)
            ];
        }
        if ($format === 'xls') {
            $this->outputXLS($rows);
        } else {
            $this->outputCSV($rows);
        }
    }

    private function formatContent($content)
    {
        // You can prettify/structure the content here if needed
        return wp_strip_all_tags($content);
    }

    private function outputCSV($rows)
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="form-orders-' . date('Ymd-His') . '.csv"');
        $output = fopen('php://output', 'w');
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    private function outputXLS($rows)
    {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="form-orders-' . date('Ymd-His') . '.xls"');
        echo "<table border='1'>";
        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
        exit;
    }

}
