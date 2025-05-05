# CustomArchivePages Module

Adds customizable archive page support for any registered custom post types (CPTs).

## Features

- Creates a private `archive_pages` CPT to store WYSIWYG content for archives.
- Adds an “Edit Archive Page” submenu under each configured CPT.
- Automatically creates the archive page on first click and assigns current language (Polylang).
- Overrides front‑end CPT archive URLs to load the static page content plus a CPT loop.
- Full REST & Gutenberg support for `archive_pages` post type.
- Polylang & Polylang Pro compatible: each archive page can have translations.

## Installation

1. Initialize the module in `functions.php`:

    ```php
    use Hacon\ThemeCore\ThemeModules\CustomArchivePages\CustomArchivePages;

    CustomArchivePages::initModule([
        'postTypes' => ['case_study', 'movies'],
    ]);
    ```
2. Save your permalinks (WP Admin > Settings > Permalinks > **Save Changes**).
3. Create/edit the archive page content via **Edit Archive Page** under the CPT menu.
4. If using **Polylang**, add translations of the archive page in **Languages > String translations** or via the page editor.

## Configuration

- `postTypes` (array): List of CPT slugs to enable custom archives for.

## Usage

- Front‑end URL (e.g. `/case-studies/`) will display your page content at the top and the CPT loop below.
- Use normal Page and CPT template parts (`page.php`, `template-parts/content-<cpt>.php`).
