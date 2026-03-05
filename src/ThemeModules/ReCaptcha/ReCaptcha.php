<?php
namespace Hacon\ThemeCore\ThemeModules\ReCaptcha;

use Hacon\ThemeCore\ThemeModules\ThemeModule;

/**
 * ReCaptchaHandler handler class (Singleton)
 */
class ReCaptcha extends ThemeModule
{
    private string $siteKey;
    private string $secretKey;

    private const OPTION_SITE_KEY   = 'hacon_recaptcha_site_key';
    private const OPTION_SECRET_KEY = 'hacon_recaptcha_secret_key';
    private const SETTINGS_SLUG     = 'hacon-recaptcha-settings';

    /**
     * Private constructor to prevent direct instantiation
     */
    protected function __construct(array $config)
    {
        $this->siteKey   = get_option(self::OPTION_SITE_KEY, '');
        $this->secretKey = get_option(self::OPTION_SECRET_KEY, '');
    }

    public function init()
    {
        add_action('admin_menu', [$this, 'registerSettingsPage']);
        add_action('admin_post_hacon_recaptcha_save', [$this, 'handleSettingsSave']);

        if (!$this->isConfigured()) {
            return;
        }

        add_action('wp_footer', function () {
            echo $this->getFrontendScripts();
        });
    }

    /**
     * Check if reCAPTCHA credentials are configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->siteKey) && !empty($this->secretKey);
    }

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    /**
     * Register the settings page under Settings menu.
     */
    public function registerSettingsPage(): void
    {
        add_options_page(
            'reCAPTCHA Settings',
            'reCAPTCHA',
            'manage_options',
            self::SETTINGS_SLUG,
            [$this, 'renderSettingsPage']
        );
    }

    /**
     * Render the admin settings page.
     */
    public function renderSettingsPage(): void
    {
        $siteKey   = get_option(self::OPTION_SITE_KEY, '');
        $secretKey = get_option(self::OPTION_SECRET_KEY, '');
        ?>
        <div class="wrap">
            <h1>reCAPTCHA Settings</h1>
            <p>Configure your Google reCAPTCHA v2 (Invisible) credentials. Get your keys from
                <a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA Admin Console</a>.
            </p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('hacon_recaptcha_save_action'); ?>
                <input type="hidden" name="action" value="hacon_recaptcha_save">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="recaptcha_site_key">Site Key</label></th>
                        <td>
                            <input type="text" id="recaptcha_site_key" name="recaptcha_site_key"
                                   value="<?php echo esc_attr($siteKey); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="recaptcha_secret_key">Secret Key</label></th>
                        <td>
                            <input type="password" id="recaptcha_secret_key" name="recaptcha_secret_key"
                                   value="<?php echo esc_attr($secretKey); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handle settings form submission.
     */
    public function handleSettingsSave(): void
    {
        check_admin_referer('hacon_recaptcha_save_action');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $siteKey   = sanitize_text_field($_POST['recaptcha_site_key'] ?? '');
        $secretKey = sanitize_text_field($_POST['recaptcha_secret_key'] ?? '');

        update_option(self::OPTION_SITE_KEY, $siteKey);
        update_option(self::OPTION_SECRET_KEY, $secretKey);

        $this->siteKey   = $siteKey;
        $this->secretKey = $secretKey;

        wp_safe_redirect(add_query_arg(
            ['page' => self::SETTINGS_SLUG, 'settings-updated' => 'true'],
            admin_url('options-general.php')
        ));
        exit;
    }

    /**
     * Get the frontend scripts for reCAPTCHA
     *
     * @return string|void HTML output for reCAPTCHA scripts
     */
    public function getFrontendScripts()
    {
        if (!$this->isConfigured())
            return;
        $escapedSiteKey = esc_attr($this->siteKey);
        ob_start();
        ?>
        <script>
            (function ()
            {
                function loadRecaptchaScript(callbackName)
                {
                    if (document.querySelector('script[src^="https://www.google.com/recaptcha/api.js"]')) return;

                    var script = document.createElement('script');
                    script.src = 'https://www.google.com/recaptcha/api.js?onload=' + callbackName + '&render=explicit';
                    script.defer = true;
                    script.async = true;
                    document.head.appendChild(script);
                }

                var containerId = 'recaptcha-container';
                var recaptchaContainer = document.getElementById(containerId);
                if (!recaptchaContainer)
                {
                    recaptchaContainer = document.createElement('div');
                    recaptchaContainer.id = containerId;
                    recaptchaContainer.style.display = 'none';
                    document.body.appendChild(recaptchaContainer);
                }

                var widgetId = null;

                window.onRecaptchaApiLoad = function ()
                {
                    widgetId = grecaptcha.render(recaptchaContainer, {
                        'sitekey': '<?= $escapedSiteKey ?>',
                        'size': 'invisible'
                    });
                };

                var loadRecaptchaOnInteraction = function ()
                {
                    loadRecaptchaScript('onRecaptchaApiLoad');
                    document.removeEventListener('mousemove', loadRecaptchaOnInteraction);
                    document.removeEventListener('click', loadRecaptchaOnInteraction);
                    document.removeEventListener('scroll', loadRecaptchaOnInteraction);
                    document.removeEventListener('keydown', loadRecaptchaOnInteraction);
                    document.removeEventListener('touchstart', loadRecaptchaOnInteraction);
                };

                document.addEventListener('mousemove', loadRecaptchaOnInteraction);
                document.addEventListener('click', loadRecaptchaOnInteraction);
                document.addEventListener('scroll', loadRecaptchaOnInteraction);
                document.addEventListener('keydown', loadRecaptchaOnInteraction);
                document.addEventListener('touchstart', loadRecaptchaOnInteraction);

                /**
                 * Retrieves a reCAPTCHA token by executing the already rendered widget.
                 * Returns a Promise that resolves with the token.
                 *
                 * Usage:
                 *    const token = await window.getRecaptchaResult();
                 */
                window.getRecaptchaResult = function ()
                {
                    return new Promise(function (resolve, reject)
                    {
                        if (typeof grecaptcha === 'undefined')
                        {
                            return reject(new Error('reCAPTCHA not loaded'));
                        }
                        if (widgetId === null)
                        {
                            var interval = setInterval(function ()
                            {
                                if (widgetId !== null)
                                {
                                    clearInterval(interval);
                                    executeRecaptcha();
                                }
                            }, 100);
                        } else
                        {
                            executeRecaptcha();
                        }

                        function executeRecaptcha()
                        {
                            try
                            {
                                grecaptcha.execute(widgetId, {
                                    'callback': function (token)
                                    {
                                        resolve(token);
                                    },
                                    'error-callback': function ()
                                    {
                                        reject(new Error('reCAPTCHA error'));
                                    }
                                });
                            } catch (e)
                            {
                                reject(e);
                            }
                        }
                    });
                };
            })();
        </script>
        <style>
            #recaptcha-container {
                display: none;
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Verify reCAPTCHA response with Google's API.
     *
     * @param string $recaptchaResponse The token from the frontend.
     * @return array Always returns ['success' => bool, 'error' => string|null]
     */
    public function verify($recaptchaResponse): array
    {
        if (!$this->isConfigured() || !$recaptchaResponse) {
            return [
                'success' => false,
                'error'   => 'Missing reCAPTCHA secret key or response'
            ];
        }

        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'timeout' => 5,
            'body'    => [
                'secret'   => $this->secretKey,
                'response' => $recaptchaResponse,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ],
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error'   => 'Failed to connect to reCAPTCHA server: ' . $response->get_error_message()
            ];
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($result)) {
            return [
                'success' => false,
                'error'   => 'Invalid response from reCAPTCHA server'
            ];
        }

        return [
            'success' => !empty($result['success']),
            'error'   => $result['success'] ? null : ($result['error-codes'] ?? 'Unknown error')
        ];
    }
}
