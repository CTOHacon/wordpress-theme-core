<?php
namespace Hacon\ThemeCore\Services\TelegramService;

class TelegramService
{
    private string $lastError = '';

    public function __construct(
        private string $botToken,
        private string $chatId,
    ) {
    }

    public function setChatId(string $chatId): void
    {
        $this->chatId = $chatId;
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function sendTelegramMessage($message): bool
    {
        $this->lastError = '';
        $apiUrl          = sprintf('https://api.telegram.org/bot%s/sendMessage', $this->botToken);
        $args            = [
            'body'    => [
                'chat_id' => $this->chatId,
                'text'    => $message,
            ],
            'timeout' => 15,
        ];
        try {
            $response = wp_remote_post($apiUrl, $args);
            if (is_wp_error($response)) {
                $this->lastError = 'WP_Error: ' . $response->get_error_message();
                error_log('Telegram error: ' . $this->lastError);
                return false;
            }

            $responseCode = wp_remote_retrieve_response_code($response);
            $body         = wp_remote_retrieve_body($response);
            $resultData   = json_decode($body, true);

            // Check if the API returned success
            if (!isset($resultData['ok']) || $resultData['ok'] !== true) {
                // Capture the full error details from Telegram API
                $errorDescription = $resultData['description'] ?? 'Unknown error';
                $errorCode        = $resultData['error_code'] ?? $responseCode;
                $this->lastError  = sprintf(
                    'Telegram API Error [%s]: %s (Response: %s)',
                    $errorCode,
                    $errorDescription,
                    $body
                );
                error_log('Telegram API response: ' . $this->lastError);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->lastError = 'Exception: ' . $e->getMessage();
            error_log('Telegram exception: ' . $this->lastError);
            return false;
        }
    }

}