<?php
function getLink($source)
{
    // ID → permalink (with Polylang support)
    if (is_numeric($source)) {
        if (function_exists('pll_get_post')) {
            $source = pll_get_post((int) $source) ?: (int) $source;
        }
        return get_permalink((int) $source) ?: '';
    }

    // Title lookup: no “http” or trailing slash, but contains spaces
    if (
        is_string($source)
        && strpos($source, 'http') === false
        && preg_match('/\s+/', $source)
    ) {
        $query = new WP_Query([
            'title'          => $source,
            'post_type'      => [
                'post',
                'page'
            ],
            'posts_per_page' => 1,
            'no_found_rows'  => true,
        ]);
        if ($query->have_posts()) {
            $post = $query->posts[0];
            wp_reset_postdata();
            return get_permalink($post->ID) ?: '';
        }
    }

    // Fallback for any other string (URL‐like or slug)
    if (is_string($source)) {
        // strip any existing trailing slash…
        $url = rtrim($source, '/');

        // if there's no query (?) or fragment (#), ensure it ends with a slash
        if (strpos($url, '?') === false && strpos($url, '#') === false) {
            $url .= '/';
        }

        return $url;
    }

    return '';
}
