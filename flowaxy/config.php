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
    'app_url' => rtrim((string) (flowaxy_env('APP_URL', '') ?? ''), '/'),
    'meta_pixel_id' => trim((string) (flowaxy_env('META_PIXEL_ID', '') ?? '')),
    'ga4_measurement_id' => trim((string) (flowaxy_env('GA4_MEASUREMENT_ID', '') ?? '')),
    'gtm_container_id' => trim((string) (flowaxy_env('GTM_CONTAINER_ID', '') ?? '')),
    'feed_secret' => trim((string) (flowaxy_env('FEED_SECRET', '') ?? '')),
    'contact_email' => trim((string) (flowaxy_env('CONTACT_EMAIL', '') ?? '')),
    'contact_telegram' => trim((string) (flowaxy_env('CONTACT_TELEGRAM', '') ?? '')),
    'log_path' => $basePath . '/storage/logs/app.log',
    'project_root' => $basePath,
    'git_repo_url' => trim((string) (flowaxy_env('GIT_REPO_URL', 'https://github.com/iteffa-works/roselira.shop.git') ?? '')),
    'git_branch' => trim((string) (flowaxy_env('GIT_BRANCH', 'main') ?? '')),
    'git_binary' => trim((string) (flowaxy_env('GIT_BINARY', '') ?? '')),
    'cron_secret' => trim((string) (flowaxy_env('CRON_SECRET', '') ?? '')),
    'recaptcha_site_key' => trim((string) (flowaxy_env('RECAPTCHA_SITE_KEY', '') ?? '')),
    'recaptcha_secret_key' => trim((string) (flowaxy_env('RECAPTCHA_SECRET_KEY', '') ?? '')),

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
