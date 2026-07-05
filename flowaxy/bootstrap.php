<?php

declare(strict_types=1);

use Flowaxy\Core\Application;
use Flowaxy\Core\Autoloader;
use Flowaxy\Core\Container;
use Flowaxy\Core\Response;
use Flowaxy\Core\Router;
use Flowaxy\Core\View;
use Flowaxy\Repositories\Contracts\AdminUserRepositoryInterface;
use Flowaxy\Repositories\Contracts\CatalogRepositoryInterface;
use Flowaxy\Repositories\Contracts\LocaleRepositoryInterface;
use Flowaxy\Repositories\Contracts\OrderRepositoryInterface;
use Flowaxy\Repositories\Contracts\SettingsRepositoryInterface;
use Flowaxy\Repositories\Sqlite\Connection;
use Flowaxy\Repositories\Sqlite\LocaleSeeder;
use Flowaxy\Repositories\Sqlite\SqliteAdminUserRepository;
use Flowaxy\Repositories\Sqlite\SqliteCatalogRepository;
use Flowaxy\Repositories\Sqlite\SqliteLocaleRepository;
use Flowaxy\Repositories\Sqlite\SqliteOrderRepository;
use Flowaxy\Repositories\Sqlite\SqliteSettingsRepository;
use Flowaxy\Services\AdminAuthService;
use Flowaxy\Services\CatalogService;
use Flowaxy\Services\CronService;
use Flowaxy\Services\ExchangeService;
use Flowaxy\Services\GitUpdateService;
use Flowaxy\Services\LocaleService;
use Flowaxy\Services\OrderService;
use Flowaxy\Services\ProductFeedService;
use Flowaxy\Services\SystemCheckService;
use Flowaxy\Services\TelegramNotificationService;
use Flowaxy\Support\OrderRateLimiter;
use Flowaxy\Support\LoginRateLimiter;

require_once __DIR__ . '/Support/helpers.php';
require_once __DIR__ . '/Support/SessionManager.php';
require_once __DIR__ . '/Support/AppState.php';

$config = require __DIR__ . '/config.php';

Flowaxy\Support\AppState::$config = $config;

if (!($config['app_debug'] ?? false)) {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

require_once __DIR__ . '/Core/Autoloader.php';

Autoloader::boot(dirname(__DIR__));

$securityHeaders = [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'SAMEORIGIN',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
];

if (($config['session_secure'] ?? false) && request_is_https()) {
    $securityHeaders['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
}

Response::setDefaultHeaders($securityHeaders);

$container = new Container();

$container->singleton(Connection::class, static function () use ($config): Connection {
    return new Connection(
        $config['storage_path'] . '/roselira.db',
        $config['storage_path'] . '/roselira.sql',
    );
});

$container->singleton(CatalogRepositoryInterface::class, static fn(Container $c): SqliteCatalogRepository => new SqliteCatalogRepository($c->make(Connection::class)));
$container->singleton(OrderRepositoryInterface::class, static fn(Container $c): SqliteOrderRepository => new SqliteOrderRepository($c->make(Connection::class)));
$container->singleton(LocaleRepositoryInterface::class, static fn(Container $c): SqliteLocaleRepository => new SqliteLocaleRepository($c->make(Connection::class)));
$container->singleton(AdminUserRepositoryInterface::class, static fn(Container $c): SqliteAdminUserRepository => new SqliteAdminUserRepository($c->make(Connection::class)));
$container->singleton(SettingsRepositoryInterface::class, static fn(Container $c): SqliteSettingsRepository => new SqliteSettingsRepository($c->make(Connection::class)));

$container->singleton(TelegramNotificationService::class, static fn(Container $c): TelegramNotificationService => new TelegramNotificationService(
    $c->make(SettingsRepositoryInterface::class),
));

$container->singleton(LocaleService::class, static function (Container $c) use ($config): LocaleService {
    return new LocaleService(
        $c->make(LocaleRepositoryInterface::class),
        $config['locales_public'],
        $config['locale_fallback'],
        $config['locale_default'],
        $config['locale_cookie'],
        $config['locale_editable'],
    );
});

$container->singleton(CatalogService::class, static fn(Container $c): CatalogService => new CatalogService(
    $c->make(CatalogRepositoryInterface::class),
    $c->make(LocaleService::class),
));

$container->singleton(OrderRateLimiter::class, static fn(): OrderRateLimiter => new OrderRateLimiter());

$container->singleton(LoginRateLimiter::class, static fn(): LoginRateLimiter => new LoginRateLimiter());

$container->singleton(OrderService::class, static function (Container $c) use ($config): OrderService {
    return new OrderService(
        $c->make(OrderRepositoryInterface::class),
        $c->make(CatalogService::class),
        $c->make(LocaleService::class),
        $c->make(TelegramNotificationService::class),
        $config['order_statuses'],
    );
});

$container->singleton(ExchangeService::class, static fn(Container $c): ExchangeService => new ExchangeService(
    $c->make(CatalogService::class),
));

$container->singleton(GitUpdateService::class, static function (Container $c) use ($config): GitUpdateService {
    return new GitUpdateService(
        $c->make(SettingsRepositoryInterface::class),
        (string) $config['project_root'],
        (string) $config['git_repo_url'],
        (string) $config['git_branch'],
    );
});

$container->singleton(ProductFeedService::class, static fn(Container $c): ProductFeedService => new ProductFeedService(
    $c->make(CatalogService::class),
));

$container->singleton(SystemCheckService::class, static function (Container $c) use ($config): SystemCheckService {
    return new SystemCheckService(
        $c->make(CatalogService::class),
        $c->make(ProductFeedService::class),
        $c->make(TelegramNotificationService::class),
        $c->make(SettingsRepositoryInterface::class),
        (string) $config['project_root'],
    );
});

$container->singleton(CronService::class, static fn(Container $c): CronService => new CronService(
    $c->make(GitUpdateService::class),
    $c->make(SystemCheckService::class),
    $c->make(SettingsRepositoryInterface::class),
));

$container->singleton(AdminAuthService::class, static function (Container $c) use ($config): AdminAuthService {
    return new AdminAuthService(
        $c->make(AdminUserRepositoryInterface::class),
        $config['admin_session_key'],
        (bool) ($config['session_secure'] ?? false),
    );
});

$container->singleton(View::class, static function (Container $c) use ($config): View {
    return new View(
        $config['views_path'],
        $config['admin_views_path'],
        $c->make(LocaleService::class),
    );
});

$container->singleton(Router::class, static function (): Router {
    $router = new Router();
    (require __DIR__ . '/routes.php')($router);

    return $router;
});

$container->singleton(Application::class, static fn(Container $c): Application => new Application(
    $c,
    $c->make(Router::class),
));

function flowaxy_run(): void
{
    global $container, $config;

    $connection = $container->make(Connection::class);
    $connection->restoreFromDumpIfEmpty();
    (new LocaleSeeder($connection))->ensure();

    $locale = $container->make(LocaleService::class);
    flowaxy_set_locale($locale);

    if ($redirect = $locale->resolveLanguageSwitch()) {
        Response::redirect($redirect)->send();

        return;
    }

    $locale->boot();
    Flowaxy\Support\SessionManager::ensureStarted($config);
    $container->make(Application::class)->run();
}
