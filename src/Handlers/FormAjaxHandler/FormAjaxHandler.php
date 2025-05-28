<?php
namespace Hacon\ThemeCore\Handlers\FormAjaxHandler;

use Hacon\ThemeCore\Services\TelegramService\TelegramService;
use Hacon\ThemeCore\ThemeModules\ReCaptcha\ReCaptcha;

class FormAjaxHandler
{
    private string          $action;
    private array           $fields                   = [];
    private bool            $recaptchaEnabled         = true;
    private TelegramService $telegramService;
    private string          $telegramBotToken         = '';
    private string          $telegramChatId           = '';
    private array           $receiverEmails           = [];
    private                 $templateCallback         = null; /* @var callable */
    private                 $emailTemplateCallback    = null; /* @var callable */
    private                 $telegramTemplateCallback = null; /* @var callable */
    private                 $wpPostTemplateCallback   = null; /* @var callable */
    private                 $wpPostType               = 'form-orders';
    private                 $formTitle                = null;
    private                 $redirect                 = "/thank-you";
    private string          $senderName               = '';
    private string          $senderEmail              = '';
    private array           $customSubmitHandlers     = [];
    /**
     * Constructor.
     *
     * @param string $action The AJAX action name.
     */
    public function __construct(string $action)
    {
        $this->action = $action;

        // Initialize default values from theme config
        $this->telegramBotToken = getThemeСonfig('telegram.botToken', '');
        $this->telegramChatId   = getThemeСonfig('telegram.chatId', '');        // Set default template callback
        $this->templateCallback = function (array $data, array $fields = []) {
            $message = "You have received a new message:\n\n";
            foreach ($data as $key => $value) {
                // Use field label if available, otherwise format the key
                $label = isset($fields[$key]['label']) ? $fields[$key]['label'] : ucfirst($key);

                if (is_array($value)) {
                    foreach ($value as $item) {
                        if ($item['type'] === 'attachment') {
                            $message .= "<a href='{$item['url']}'>{$item['file_name']}</a>\n";
                        }
                    }
                } else {
                    $message .= $label . ': ' . $value . "\n";
                }
            }
            return $message;
        };
    }

    /**
     * Add a field with its parameters.
     *
     * @param string $fieldName The field name.
     * @param array  $params Field parameters including rules, modifier, label, etc.
     *                       - 'rules' (array): Validation rules like ['required', 'email']
     *                       - 'modifier' (callable): Optional function to modify value after validation
     *                       - 'label' (string): Field label for templating (defaults to formatted field name)
     *                       - Any other custom data for use in templates
     * @return self
     */
    public function addField(string $fieldName, array $params): self
    {
        // Ensure rules is an array
        if (!isset($params['rules'])) {
            $params['rules'] = [];
        }

        // Set default label if not provided
        if (!isset($params['label'])) {
            $params['label'] = ucwords(str_replace([
                '_',
                '-'
            ], ' ', $fieldName));
        }

        $this->fields[$fieldName] = $params;
        return $this;
    }

    /**
     * Disable reCAPTCHA verification.
     *
     * @return self
     */
    public function disableRecaptcha(): self
    {
        $this->recaptchaEnabled = false;
        return $this;
    }

    /**
     * Set the Telegram credentials.
     *
     * @param string $botToken The Telegram bot token.
     * @param string $chatId   The Telegram chat ID.
     * @return self
     */
    public function setTelegramCredentials(string $botToken, string $chatId): self
    {
        $this->telegramService = new TelegramService($botToken, $chatId);
        return $this;
    }

    /**
     * Set a custom DEFAULT template callback.
     *
     * The callback receives array $data and array $fields and should return a string.
     *
     * @param callable $callback
     * @return self
     */
    public function setTemplate(callable $callback): self
    {
        $this->templateCallback = $callback;
        return $this;
    }

    /**
     * Set a custom email template callback.
     *
     * The callback receives array $data and array $fields and should return a string.
     *
     * @param callable $callback
     * @return self
     */
    public function setEmailTemplate(callable $callback): self
    {
        $this->emailTemplateCallback = $callback;
        return $this;
    }

    /**
     * Set a custom Telegram template callback.
     *
     * The callback receives array $data and array $fields and should return a string.
     *
     * @param callable $callback
     * @return self
     */
    public function setTelegramTemplate(callable $callback): self
    {
        $this->telegramTemplateCallback = $callback;
        return $this;
    }

    /**
     * Set the receiver email(s).
     *
     * @param string|array $emails The receiver email(s).
     * @return self
     */
    public function setReceiverEmails(array $emails): self
    {
        $this->receiverEmails = $emails;
        return $this;
    }

    /**
     * Set a custom WP post template callback.
     *
     * The callback receives array $data and array $fields and should return a string.
     *
     * @param callable $callback
     * @return self
     */
    public function setWpPostTemplate(callable $callback): self
    {
        $this->wpPostTemplateCallback = $callback;
        return $this;
    }

    /**
     * Set the WP post type.
     *
     * @param string $postType The WP post type.
     * @return self
     */
    public function setWpPostType(string $postType): self
    {
        $this->wpPostType = $postType;
        return $this;
    }

    /**
     * Set the WP post title callback.
     *
     * The callback receives array $data and should return a string.
     *
     * @param callable $callback
     * @return self
     */
    public function setFormTitle(callable $callback): self
    {
        $this->formTitle = function (array $data) use ($callback) {
            return $callback($data);
        };
        return $this;
    }

    /**
     * Set the redirect you page URL.
     *
     * @param string $url The thank you page URL.
     * @return self
     */
    public function setRedirect(string $url): self
    {
        $this->redirect = $url;
        return $this;
    }

    /**
     * Set the sender name and email for outgoing emails.
     *
     * @param string $name The sender name to display in email clients.
     * @param string $email The sender email address.
     * @return self
     */
    public function setEmailSenderInfo(string $name, string $email = ''): self
    {
        $this->senderName  = $name;
        $this->senderEmail = $email;
        return $this;
    }

    /**
     * Add a custom submit handler for CRM integrations or other purposes.
     *
     * The callback receives array $data and array $fields and should return a boolean indicating success.
     *
     * @param callable $handler The handler callback function.
     * @param string $name Optional name for the handler (for debugging/logging).
     * @return self
     */
    public function addCustomSubmitHandler(callable $handler, string $name = ''): self
    {
        $this->customSubmitHandlers[] = [
            'handler' => $handler,
            'name'    => $name ?: 'Handler ' . (count($this->customSubmitHandlers) + 1)
        ];
        return $this;
    }

    /**
     * Register the AJAX handler.
     *
     * Registers both logged-in and not-logged-in handlers.
     *
     * @return void
     */
    public function registerAjaxHandler(): void
    {
        registerAjaxAction($this->action, [
            $this,
            'handleRequest'
        ]);
    }

    /**
     * init method for legacy support.
     */
    public function init(): void
    {
        $this->registerAjaxHandler();
    }

    /**
     * Main AJAX handler.
     *
     * Processes input, validates, optionally verifies reCAPTCHA,
     * sends an email and (optionally) a Telegram message,
     * and returns a JSON response.
     *
     * @return void
     */
    public function handleRequest(): void
    {
        $errors = [];
        $data   = [];
        $fields = []; // Will contain all field data including values
        // Temporary storage for files to upload after validations.
        $filesToUpload = [];

        // Process and validate each registered field.
        foreach ($this->fields as $fieldName => $fieldParams) {
            $rules = $fieldParams['rules'] ?? [];

            // Initialize field data with all parameters
            $fieldData          = $fieldParams;
            $fieldData['value'] = null;

            // Check if the field is a file upload.
            if (isset($_FILES[$fieldName])) {
                $file = $_FILES[$fieldName];

                // Check if a file is required.
                if (in_array('required', $rules, true) && $file['error'] === UPLOAD_ERR_NO_FILE) {
                    $errors[$fieldName] = 'This file field is required.';
                    continue;
                }

                // If a file is uploaded.
                if ($file['error'] === UPLOAD_ERR_OK) {
                    // Validate max size if provided (in MB).
                    if (isset($rules['max_size'])) {
                        $maxBytes = $rules['max_size'] * 1024 * 1024;
                        if ($file['size'] > $maxBytes) {
                            $errors[$fieldName] = 'The file exceeds the maximum allowed size of ' . $rules['max_size'] . ' MB.';
                            continue;
                        }
                    }
                    // Validate file type: if rule includes 'image', check if it's a valid image.
                    if (in_array('image', $rules, true)) {
                        $check = getimagesize($file['tmp_name']);
                        if ($check === false) {
                            $errors[$fieldName] = 'Uploaded file is not a valid image.';
                            continue;
                        }
                    }
                    // Additional file rules can be added here if needed.

                    // Save the file for later upload if all validations pass.
                    $filesToUpload[$fieldName] = $file;
                    $fieldData['value']        = $file;
                } else {
                    // If some error occurred other than no file.
                    if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                        $errors[$fieldName] = 'File upload error code: ' . $file['error'];
                    }
                }
            } else {
                // Process non-file fields.
                $value = isset($_POST[$fieldName]) ? sanitize_text_field(wp_unslash($_POST[$fieldName])) : '';

                if (in_array('required', $rules, true) && empty($value)) {
                    $errors[$fieldName] = 'This field is required.';
                }
                if (in_array('email', $rules, true) && !is_email($value)) {
                    $errors[$fieldName] = 'Please enter a valid email address.';
                }
                if (in_array('numeric', $rules, true) && !is_numeric($value)) {
                    $errors[$fieldName] = 'Please enter a valid number.';
                }
                if (in_array('tel', $rules, true)) {
                    $onlyDigits = preg_replace('/\D/', '', $value);
                    if (strlen($onlyDigits) < 10) {
                        $errors[$fieldName] = 'Please enter a valid phone number.';
                    }
                }
                if (isset($rules['min_length']) && strlen($value) < $rules['min_length']) {
                    $errors[$fieldName] = 'This field must be at least ' . $rules['min_length'] . ' characters long.';
                }
                if (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
                    $errors[$fieldName] = 'This field must be no more than ' . $rules['max_length'] . ' characters long.';
                }
                if (isset($rules['regex']) && !preg_match($rules['regex'], $value)) {
                    $errors[$fieldName] = 'This field does not match the required format.';
                }

                // Apply modifier if provided
                if (isset($fieldParams['modifier']) && is_callable($fieldParams['modifier'])) {
                    $value = call_user_func($fieldParams['modifier'], $value);
                }

                $data[$fieldName]   = $value;
                $fieldData['value'] = $value;
            }

            $fields[$fieldName] = $fieldData;
        }        // If no errors so far, process the file uploads.
        if (empty($errors)) {
            foreach ($filesToUpload as $field => $file) {
                $mediaFileId    = storeUploadInMediaGallery($file);
                $data[$field][] = [
                    'type'          => 'attachment',
                    'url'           => wp_get_attachment_url($mediaFileId),
                    'file_name'     => basename($file['name']),
                    'attachment_id' => $mediaFileId,
                ];

                // Update field data with processed file info
                $fields[$field]['value'] = $data[$field];
            }
        }

        // Optionally verify reCAPTCHA.
        if ($this->recaptchaEnabled && ReCaptcha::getInstance()->getSecretKey()) {
            if (!empty($_POST['g-recaptcha-response'])) {
                $recaptchaResponse = sanitize_text_field(wp_unslash($_POST['g-recaptcha-response']));
                if (!ReCaptcha::getInstance()->verify($recaptchaResponse)['success']) {
                    $errors['recaptcha'] = 'reCAPTCHA verification failed.';
                }
            } else {
                $errors['recaptcha'] = 'reCAPTCHA response missing.';
            }
        }

        // If errors exist, return them.
        if (!empty($errors)) {
            $this->sendResponse(false, null, $errors);
        }

        // Send email notification.
        $emailSent    = $this->sendEmail($data, $fields);        // Send Telegram message if credentials are set.
        $telegramSent = true;
        if (!empty($this->telegramBotToken) && !empty($this->telegramChatId)) {
            if (!isset($this->telegramService)) {
                $this->telegramService = new TelegramService($this->telegramBotToken, $this->telegramChatId);
            }
            $telegramMessage = is_callable($this->telegramTemplateCallback)
                ? call_user_func($this->telegramTemplateCallback, $data, $fields)
                : call_user_func($this->templateCallback, $data, $fields);
            $telegramSent    = $this->telegramService->sendTelegramMessage($telegramMessage);
        }

        // Create a WP post with the submitted data.
        $wpPostCreated = $this->createWpPost($data, $fields);

        // Execute custom submit handlers.
        $customHandlersExecuted = $this->executeCustomSubmitHandlers($data, $fields);

        if ($emailSent && $telegramSent && $wpPostCreated && $customHandlersExecuted) {
            $this->sendResponse(true, 'Your message was sent successfully.');
        } else {
            $this->sendResponse(false, 'There was an error sending your message.');
        }
    }

    /**
     * Send an email notification.
     *
     * @param array $data The sanitized form data.
     * @param array $fields The complete field data including values and metadata.
     * @return bool
     */
    private function sendEmail(array $data, array $fields = []): bool
    {
        $subject = 'New Contact Form Submission';
        if (is_callable($this->formTitle)) {
            $subject = call_user_func($this->formTitle, $data);
        }
        $message         = is_callable($this->emailTemplateCallback)
            ? call_user_func($this->emailTemplateCallback, $data, $fields)
            : call_user_func($this->templateCallback, $data, $fields);        // Set up WordPress filters for email sender info
        $fromEmailFilter = null;
        $fromNameFilter  = null;

        if ($this->senderEmail) {
            $senderEmail     = $this->senderEmail; // Capture value for closure
            $fromEmailFilter = function () use ($senderEmail) {
                return $senderEmail;
            };
            add_filter('wp_mail_from', $fromEmailFilter, 999);
        }

        if ($this->senderName) {
            $senderName     = $this->senderName; // Capture value for closure
            $fromNameFilter = function () use ($senderName) {
                return $senderName;
            };
            add_filter('wp_mail_from_name', $fromNameFilter, 999);
        }

        foreach ($this->receiverEmails ?? [] as $email) {
            $to      = $email;
            $headers = ['Content-Type: text/html; charset=UTF-8'];
            wp_mail($to, $subject, $message, $headers);
        }

        // Remove the filters to avoid affecting other emails
        if ($fromEmailFilter) {
            remove_filter('wp_mail_from', $fromEmailFilter, 999);
        }
        if ($fromNameFilter) {
            remove_filter('wp_mail_from_name', $fromNameFilter, 999);
        }

        return true;
    }

    /**
     * Create a form-orders post with the submitted data.
     * 
     * @param array $data The sanitized form data.
     * @param array $fields The complete field data including values and metadata.
     * @return bool 
     */
    private function createWpPost(array $data, array $fields = []): bool
    {
        $postContent = is_callable($this->wpPostTemplateCallback)
            ? call_user_func($this->wpPostTemplateCallback, $data, $fields)
            : call_user_func($this->templateCallback, $data, $fields);

        $postTitle = 'New Form Submit';
        if (is_callable($this->formTitle)) {
            $postTitle = call_user_func($this->formTitle, $data);
        }
        $post = [
            'post_title'   => $postTitle,
            'post_content' => $postContent,
            'post_status'  => 'publish',
            'post_type'    => $this->wpPostType,
        ];

        $postId = wp_insert_post($post);
        return $postId !== 0;
    }

    /**
     * Execute all custom submit handlers.
     *
     * @param array $data The sanitized form data.
     * @param array $fields The complete field data including values and metadata.
     * @return bool True if all handlers succeeded, false otherwise.
     */
    private function executeCustomSubmitHandlers(array $data, array $fields = []): bool
    {
        foreach ($this->customSubmitHandlers as $handlerInfo) {
            try {
                $result = call_user_func($handlerInfo['handler'], $data, $fields);
                if (!$result) {
                    // Log the failure if needed
                    error_log('Custom submit handler "' . $handlerInfo['name'] . '" failed');
                    return false;
                }
            } catch (\Exception $e) {
                // Log the exception if needed
                error_log('Custom submit handler "' . $handlerInfo['name'] . '" threw exception: ' . $e->getMessage());
                return false;
            }
        }
        return true;
    }

    /**
     * Output JSON response and terminate execution.
     *
     * @param array $payload The JSON response payload.
     * @return void
     */
    private function outputResponse(array $payload): void
    {
        header('Content-Type: application/json');
        echo wp_json_encode($payload);
        wp_die();
    }

    /**
     * Send a JSON response.
     *
     * @param bool  $success Whether the operation succeeded.
     * @param mixed $data    Message or additional data.
     * @param array $errors  Optional errors array.
     * @return void
     */
    private function sendResponse(bool $success, $data, array $errors = []): void
    {
        $response = [
            'success' => $success,
            'data'    => $data,
        ];

        if ($success)
            $response['redirect'] = $this->redirect;


        if (!empty($errors))
            $response['errors'] = $errors;


        $this->outputResponse($response);
    }

}
