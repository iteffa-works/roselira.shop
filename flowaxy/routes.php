<?php

declare(strict_types=1);

use Flowaxy\Admin\Controllers\AuthController;
use Flowaxy\Admin\Controllers\CatalogController as AdminCatalogController;
use Flowaxy\Admin\Controllers\DashboardController;
use Flowaxy\Admin\Controllers\LocalesController;
use Flowaxy\Admin\Controllers\OrdersController as AdminOrdersController;
use Flowaxy\Admin\Controllers\ProductController as AdminProductController;
use Flowaxy\Admin\Controllers\DatabaseController;
use Flowaxy\Admin\Controllers\NotificationsController;
use Flowaxy\Admin\Controllers\PagesController;
use Flowaxy\Admin\Controllers\RatesController;
use Flowaxy\Admin\Controllers\SecurityController;
use Flowaxy\Admin\Controllers\SystemController;
use Flowaxy\Controllers\FeedController;
use Flowaxy\Controllers\HomeController;
use Flowaxy\Controllers\OrderController;
use Flowaxy\Controllers\PageController;
use Flowaxy\Controllers\ProductController;
use Flowaxy\Controllers\TrackController;
use Flowaxy\Core\Router;

return static function (Router $router): void {
    $router->get('/', HomeController::class . '::index');
    $router->post('/order', OrderController::class . '::store');
    $router->post('/track', TrackController::class . '::collect');
    $router->get('/privacy', PageController::class . '::privacy');
    $router->get('/terms', PageController::class . '::terms');
    $router->get('/delivery', PageController::class . '::delivery');
    $router->get('/sitemap.xml', SeoController::class . '::sitemap');
    $router->get('/feeds/meta.xml', FeedController::class . '::meta');
    $router->get('/feeds/google.xml', FeedController::class . '::google');

    $router->get('/admin', DashboardController::class . '::index');
    $router->get('/admin/login', AuthController::class . '::loginForm');
    $router->post('/admin/login', AuthController::class . '::login');
    $router->get('/admin/logout', AuthController::class . '::logout');
    $router->get('/admin/install', AuthController::class . '::installForm');
    $router->post('/admin/install', AuthController::class . '::install');
    $router->get('/admin/catalog', AdminCatalogController::class . '::index');
    $router->get('/admin/product', AdminProductController::class . '::edit');
    $router->post('/admin/product', AdminProductController::class . '::update');
    $router->get('/admin/orders', AdminOrdersController::class . '::index');
    $router->post('/admin/orders', AdminOrdersController::class . '::updateStatus');
    $router->post('/admin/orders/delete', AdminOrdersController::class . '::delete');
    $router->get('/admin/database', DatabaseController::class . '::index');
    $router->post('/admin/database', DatabaseController::class . '::cleanup');
    $router->get('/admin/locales', LocalesController::class . '::index');
    $router->post('/admin/locales', LocalesController::class . '::save');
    $router->get('/admin/pages', PagesController::class . '::index');
    $router->post('/admin/pages', PagesController::class . '::save');
    $router->get('/admin/rates', RatesController::class . '::index');
    $router->post('/admin/rates', RatesController::class . '::update');
    $router->get('/admin/notifications', NotificationsController::class . '::index');
    $router->post('/admin/notifications', NotificationsController::class . '::save');
    $router->post('/admin/notifications/test', NotificationsController::class . '::test');
    $router->get('/admin/system', SystemController::class . '::index');
    $router->get('/admin/security', SecurityController::class . '::index');
    $router->post('/admin/security/action', SecurityController::class . '::action');
    $router->post('/admin/system/git-pull', SystemController::class . '::gitPull');
    $router->post('/admin/system/checks', SystemController::class . '::runChecks');
    $router->post('/admin/system/cron', SystemController::class . '::runCron');
    $router->get('/admin/updates', SystemController::class . '::index');

    $router->get('/{slug}', ProductController::class . '::show');
};
