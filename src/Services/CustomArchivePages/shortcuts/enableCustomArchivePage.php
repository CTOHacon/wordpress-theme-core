<?php
use Hacon\ThemeCore\Services\CustomArchivePages\CustomArchivePagesService;

/**
 * Enable custom archive page for a post type.
 * Usage: enableCustomArchivePage('your_post_type');
 */
function enableCustomArchivePage(string $postType): void
{
    CustomArchivePagesService::enable($postType);
}
