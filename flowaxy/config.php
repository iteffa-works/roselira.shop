<?php

declare(strict_types=1);

require_once __DIR__ . '/Support/env.php';

flowaxy_load_env(dirname(__DIR__) . '/.env');

$basePath = dirname(__DIR__);
$appEnv = flowaxy_env('APP_ENV', 'local') ?? 'local';

return [
    'app_env' => $appEnv,
    'app_debug' => flowaxy_env_bool('APP_DEBUG', $appEnv !== 'production'),
    'session_secure' => flowaxy_env_bool('SESSION_SECURE', $appEnv === 'production'),

    'views_path' => $basePath . '/views',
    'admin_views_path' => __DIR__ . '/Admin/Views',
    'storage_path' => $basePath . '/storage',

    'locales_public' => ['ru', 'uk'],
    'locale_fallback' => 'en',
    'locale_default' => 'uk',
    'locale_cookie' => 'flowaxy_lang',
    'locale_editable' => ['uk', 'ru'],

    'order_statuses' => ['new', 'done', 'cancelled'],
    'admin_session_key' => 'flowaxy_admin',
];
