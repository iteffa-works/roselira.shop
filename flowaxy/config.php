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
    'app_url' => rtrim(flowaxy_env_value('APP_URL'), '/'),
    'meta_pixel_id' => flowaxy_env_value('META_PIXEL_ID'),
    'ga4_measurement_id' => flowaxy_env_value('GA4_MEASUREMENT_ID'),
    'gtm_container_id' => flowaxy_env_value('GTM_CONTAINER_ID'),
    'ga4_property_id' => flowaxy_env_value('GA4_PROPERTY_ID'),
    'ga4_service_account_json' => flowaxy_env_value('GA4_SERVICE_ACCOUNT_JSON'),
    'ga4_looker_embed_url' => flowaxy_env_value('GA4_LOOKER_EMBED_URL'),
    'feed_secret' => flowaxy_env_value('FEED_SECRET'),
    'contact_email' => flowaxy_env_value('CONTACT_EMAIL'),
    'contact_telegram' => flowaxy_env_value('CONTACT_TELEGRAM'),
    'log_path' => $basePath . '/storage/logs/app.log',
    'project_root' => $basePath,
    'git_repo_url' => trim((string) (flowaxy_env('GIT_REPO_URL', 'https://github.com/iteffa-works/roselira.shop.git') ?? '')),
    'git_branch' => trim((string) (flowaxy_env('GIT_BRANCH', 'main') ?? '')),
    'git_binary' => flowaxy_env_value('GIT_BINARY'),
    'cron_secret' => flowaxy_env_value('CRON_SECRET'),
    'recaptcha_site_key' => flowaxy_env_value('RECAPTCHA_SITE_KEY'),
    'recaptcha_secret_key' => flowaxy_env_value('RECAPTCHA_SECRET_KEY'),

    'views_path' => $basePath . '/views',
    'admin_views_path' => __DIR__ . '/Admin/Views',
    'storage_path' => $basePath . '/storage',

    'locales_public' => ['ru', 'uk'],
    'locale_fallback' => 'en',
    'locale_default' => 'uk',
    'locale_cookie' => 'flowaxy_lang',
    'locale_editable' => ['uk', 'ru'],

    'order_statuses' => ['new', 'done', 'cancelled'],
    'order_status_labels' => [
        'new' => 'Нове',
        'done' => 'Виконано',
        'cancelled' => 'Скасовано',
    ],
    'order_status_filter_labels' => [
        'new' => 'Нові',
        'done' => 'Виконані',
        'cancelled' => 'Скасовані',
    ],
    'admin_session_key' => 'flowaxy_admin',
];
