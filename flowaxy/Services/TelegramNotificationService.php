<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Repositories\Contracts\SettingsRepositoryInterface;
use Flowaxy\Support\Logger;

final class TelegramNotificationService
{
    private const KEY_ENABLED = 'telegram_enabled';
    private const KEY_BOT_TOKEN = 'telegram_bot_token';
    private const KEY_CHAT_ID = 'telegram_chat_id';
    private const KEY_IS_FORUM = 'telegram_is_forum';
    private const KEY_THREAD_ID = 'telegram_thread_id';

    public function __construct(private readonly SettingsRepositoryInterface $settings)
    {
    }

    /** @return array{enabled: bool, bot_token: string, chat_id: string, is_forum: bool, thread_id: string} */
    public function getConfig(): array
    {
        return [
            'enabled' => $this->settings->get(self::KEY_ENABLED, '0') === '1',
            'bot_token' => (string) $this->settings->get(self::KEY_BOT_TOKEN, ''),
            'chat_id' => (string) $this->settings->get(self::KEY_CHAT_ID, ''),
            'is_forum' => $this->settings->get(self::KEY_IS_FORUM, '0') === '1',
            'thread_id' => (string) $this->settings->get(self::KEY_THREAD_ID, ''),
        ];
    }

    /** @param array{enabled?: bool, bot_token?: string, chat_id?: string, is_forum?: bool, thread_id?: string} $input */
    public function saveConfig(array $input): bool
    {
        return $this->settings->setMany([
            self::KEY_ENABLED => !empty($input['enabled']) ? '1' : '0',
            self::KEY_BOT_TOKEN => trim((string) ($input['bot_token'] ?? '')),
            self::KEY_CHAT_ID => trim((string) ($input['chat_id'] ?? '')),
            self::KEY_IS_FORUM => !empty($input['is_forum']) ? '1' : '0',
            self::KEY_THREAD_ID => trim((string) ($input['thread_id'] ?? '')),
        ]);
    }

    public function sendTestMessage(): bool
    {
        $lines = ['Тестове сповіщення Roselira. Telegram підключено.'];
        if ($site = $this->siteLabel()) {
            $lines[] = 'Сайт: ' . $site;
        }

        return $this->sendMessage(implode("\n", $lines));
    }

    /** @param array<string, mixed> $order */
    public function notifyNewOrder(array $order): bool
    {
        $config = $this->getConfig();
        if (!$config['enabled']) {
            return false;
        }

        $price = isset($order['price'])
            ? formatPrice((float) $order['price'], (string) ($order['price_currency'] ?? 'UAH'))
            : '—';

        $lines = [
            'Нове замовлення #' . ($order['id'] ?? ''),
            '',
            'Товар: ' . ($order['product_name'] ?? $order['product_slug'] ?? ''),
            'Відтінок: ' . ($order['variant_name'] ?? $order['variant_id'] ?? '—'),
            'Ціна: ' . $price,
            'Клієнт: ' . ($order['customer_name'] ?? ''),
            'Телефон: ' . ($order['customer_phone'] ?? ''),
        ];

        if (!empty($order['comment'])) {
            $lines[] = 'Коментар: ' . (string) $order['comment'];
        }

        if ($site = $this->siteLabel()) {
            $lines[] = 'Сайт: ' . $site;
        }

        return $this->sendMessage(implode("\n", $lines));
    }

    private function siteLabel(): ?string
    {
        $url = rtrim((string) (app_config()['app_url'] ?? ''), '/');
        if ($url === '') {
            return null;
        }

        $label = preg_replace('#^https?://#i', '', $url);

        return is_string($label) && $label !== '' ? $label : null;
    }

    private function sendMessage(string $text): bool
    {
        $config = $this->getConfig();
        $token = $config['bot_token'];
        $chatId = $config['chat_id'];

        if ($token === '' || $chatId === '') {
            return false;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'disable_web_page_preview' => true,
        ];

        if ($config['is_forum'] && $config['thread_id'] !== '') {
            $payload['message_thread_id'] = (int) $config['thread_id'];
        }

        $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nUser-Agent: RoseliraFlowaxy/1.0\r\n",
                'content' => (string) json_encode($payload, JSON_UNESCAPED_UNICODE),
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            Logger::error('Telegram API request failed');

            return false;
        }

        $response = json_decode($raw, true);

        if (!is_array($response) || ($response['ok'] ?? false) !== true) {
            Logger::error('Telegram API error', ['response' => $raw]);

            return false;
        }

        return true;
    }
}
