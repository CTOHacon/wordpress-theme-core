# Hacon ThemeCore

> A set of core features for Hacon WordPress themes.

`hacon/theme-core` is a PSR-4 Composer library that provides the shared building blocks used across Hacon WordPress themes: a singleton **module** system, reusable **services**, a component **templating** layer, AJAX form handling, and a large set of WordPress/utility **helper** functions.

- **Package:** `hacon/theme-core`
- **Namespace:** `Hacon\ThemeCore\`
- **Type:** library (autoloaded into a theme)
- **License:** MIT

## Installation

Install into a theme via Composer:

```bash
composer require hacon/theme-core
```

Then load Composer's autoloader from the theme bootstrap (e.g. `functions.php`):

```php
require_once get_template_directory() . '/vendor/autoload.php';
```

Autoloading is configured in [composer.json](composer.json):

- **PSR-4:** `Hacon\ThemeCore\` → [`src/`](src/)
- **Files:** [`src/load-functions.php`](src/load-functions.php) is auto-required and bootstraps all helpers + service `helpers`/`shortcuts` folders.

On load, [load-functions.php](src/load-functions.php) recursively includes every PHP file under [`src/Helpers`](src/Helpers/), then includes any `helpers/` and `shortcuts/` folders found under [`src/Services`](src/Services/). This makes the global helper and shortcut functions available everywhere.

## Concepts

### Theme Modules

Each feature is a **Theme Module** — a singleton extending [`ThemeModule`](src/ThemeModules/ThemeModule.php) (which implements [`ThemeModuleInterface`](src/Contracts/ThemeModuleInterface.php)). Modules are initialized once with an optional config array:

```php
use Hacon\ThemeCore\ThemeModules\ThemeAssetsLoader\ThemeAssetsLoader;

ThemeAssetsLoader::initModule([
    'enqueFrontendCSS' => ['assets/css/app.css'],
    'enqueFrontendJS'  => ['assets/js/app.js'],
    'disableDefaultWPAssetsForFrontend' => ['jquery'],
]);
```

`initModule(array $config = [])` constructs the singleton (passing `$config` to the constructor) and calls `init()`. Calling it again is a no-op. `getInstance()` returns the live instance. Cloning and unserializing are blocked.

### Configuration

[`ConfigurationService`](src/Services/ConfigurationService/ConfigurationService.php) reads config files from the theme's `config/` directory using dot notation. It tries `config/<name>.php` (returning an array) first, then `config/<name>.json`.

```php
// Reads config/telegram.php → ['botToken' => '...', 'chatId' => '...']
$token = getThemeСonfig('telegram.botToken', '');
```

> Note: the global shortcut is `getThemeСonfig()` (see [shortcut](src/Services/ConfigurationService/shortcuts/getThemeСonfig.php)). Passing `null` as the key returns the service instance.

### Component Templating

Render PHP component templates with HTML attributes and props via [`ComponentRenderService`](src/Services/TemplatingService/ComponentRenderService.php):

```php
// Preferred API
render_component_template('button', '/components/button/button.php', ['class' => 'btn'], ['label' => 'Send']);

// Deprecated — use render_component_template() instead
component('button', ['class' => 'btn'], ['label' => 'Send']);
```

HTML attribute helpers ([`HtmlAttributesService`](src/Services/TemplatingService/HtmlAttributesService.php)) are exposed as `assembleHtmlAttributes()` and `arrayToHtmlAttributes()`.

## Available Modules

| Module | Purpose |
|--------|---------|
| [`ThemeAssetsLoader`](src/ThemeModules/ThemeAssetsLoader/ThemeAssetsLoader.php) | Register/enqueue CSS & JS per context (frontend, admin, block editor, inline, lazy); strip default WP assets |
| [`ACF`](src/ThemeModules/ACF/ACF.php) | Advanced Custom Fields integration / setup |
| [`Polylang`](src/ThemeModules/Polylang/Polylang.php) | Polylang multilingual helpers and string registration |
| [`ReCaptcha`](src/ThemeModules/ReCaptcha/ReCaptcha.php) | reCAPTCHA settings page + verification |
| [`Seeders`](src/ThemeModules/Seeders/Seeders.php) | Data seeders, run via `?seeders=name,other` URL param |
| [`FormOrdersPostType`](src/ThemeModules/FormOrdersPostType/FormOrdersPostType.php) | `form-orders` post type + admin export of form submissions |
| [`GutenbergBlocksWhitelist`](src/ThemeModules/GutenbergBlocksWhitelist/GutenbergBlocksWhitelist.php) | Restrict allowed Gutenberg block types |
| [`EnableSVG`](src/ThemeModules/EnableSVG/EnableSVG.php) | Allow safe SVG uploads |
| [`FaviconInjector`](src/ThemeModules/FaviconInjector/FaviconInjector.php) | Inject favicons into head (frontend, admin, login) |
| [`HotReload`](src/ThemeModules/HotReload/HotReload.php) | Dev hot-reload trigger script |
| [`InternalNavigationPrefetch`](src/ThemeModules/InternalNavigationPrefetch/InternalNavigationPrefetch.php) | Prefetch internal links for faster navigation |
| [`PageAutoTableOfContetns`](src/ThemeModules/PageAutoTableOfContetns/PageAutoTableOfContetns.php) | Auto table of contents from page headings |
| [`PostColorTheme`](src/ThemeModules/PostColorTheme/PostColorTheme.php) | Per-post color theme + body class |
| [`ScrollSaver`](src/ThemeModules/ScrollSaver/ScrollSaver.php) | Persist/restore scroll position |
| [`PathPatternCache`](src/ThemeModules/PathPatternCache/PathPatternCache.php) | Cache resolved path patterns |
| [`AdminMenuGroupsRegister`](src/ThemeModules/AdminMenuGroupsRegister/AdminMenuGroupsRegister.php) | Group admin menu items |
| [`BodyWidthCssComputedVariable`](src/ThemeModules/BodyWidthCssComputedVariable/BodyWidthCssComputedVariable.php) | Expose body width as a CSS variable |
| [`DocumentScrollbarWidthCssVariable`](src/ThemeModules/DocumentScrollbarWidthCssVariable/DocumentScrollbarWidthCssVariable.php) | Expose scrollbar width as a CSS variable |
| [`PreventOnLoadCssTransitions`](src/ThemeModules/PreventOnLoadCssTransitions/PreventOnLoadCssTransitions.php) | Suppress CSS transitions on page load |

## Services

| Service | Purpose |
|---------|---------|
| [`ConfigurationService`](src/Services/ConfigurationService/ConfigurationService.php) | Dot-notation config access from theme `config/` |
| [`ComponentRenderService`](src/Services/TemplatingService/ComponentRenderService.php) | Render component PHP templates with props & attributes |
| [`HtmlAttributesService`](src/Services/TemplatingService/HtmlAttributesService.php) | Build HTML attribute strings from arrays |
| [`TelegramService`](src/Services/TelegramService/TelegramService.php) | Send messages to Telegram (used by form handler) |
| [`CustomArchivePagesService`](src/Services/CustomArchivePages/CustomArchivePagesService.php) | Map custom pages to post-type archives |
| [`PathPatternCacheManager`](src/Services/PathPatternCacheManager/PathPatternCacheManager.php) | Backing cache for `PathPatternCache` |

## Form Handling

[`FormAjaxHandler`](src/Handlers/FormAjaxHandler/FormAjaxHandler.php) wires up an AJAX form action with reCAPTCHA, email + BCC delivery, Telegram notification, optional WP post creation, redirect, and custom submit handlers. Defaults (Telegram token/chat id) are pulled from theme config.

```php
use Hacon\ThemeCore\Handlers\FormAjaxHandler\FormAjaxHandler;

$handler = new FormAjaxHandler('contact_form');
// configure fields, receivers, templates, redirect...
```

## Helpers

Global functions auto-loaded from [`src/Helpers`](src/Helpers/):

- **WordPress** ([`Helpers/wordpress`](src/Helpers/wordpress/)): `getImageData`, `getCurrentPostID`, `registerAjaxAction`, `disableWpEmoji`, `disablePostsPostType`, `getLink`, `removePostTypeArchiveSlug`, `storeUploadInMediaGallery`, `disableComments`, `ultimateGetPost`
- **Utils** ([`Helpers/utils`](src/Helpers/utils/)): `decodeUnicodeContent`, `isPathActive`, `_dump`, `sanitizePhone`, `getThemeFileUri`, `getThemeFilePath`, `prettyLog`, `requireAll`, `PatternScanner`

## Project Layout

```
src/
├── load-functions.php        # bootstrap: include helpers + service shortcuts
├── Contracts/                # ThemeModuleInterface
├── ThemeModules/             # singleton feature modules (ThemeModule base)
├── Services/                 # config, templating, telegram, archives, caching
│   └── */shortcuts|helpers/  # global function shortcuts (auto-loaded)
├── Handlers/                 # FormAjaxHandler
├── Processors/               # AutoTableOfContentsProcessor
└── Helpers/                  # global WordPress + utility functions
```

## Releasing

Tags drive releases. Run the helper to bump and push a tag (GitHub Actions creates the release):

```bash
./release.sh   # choose patch / minor / major
```

## License

MIT © Hacon
```