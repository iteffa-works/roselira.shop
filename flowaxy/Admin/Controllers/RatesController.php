<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Services\AdminAuthService;
use Flowaxy\Services\ExchangeService;

final class RatesController extends AdminController
{
    public function __construct(
        View $view,
        AdminAuthService $auth,
        private readonly ExchangeService $exchange,
    ) {
        parent::__construct($view, $auth);
    }

    public function index(): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        $content = $this->view->renderAdmin('rates', [
            'rates' => $this->exchange->getRates(),
            'csrf' => $this->auth->csrfToken(),
            'exampleUah' => $this->exchange->convertToUah(8.99, 'EUR'),
        ]);

        return $this->renderPage($content, 'Курси', 'rates');
    }

    public function update(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        if ($response = $this->verifyPost($request)) {
            return $response;
        }

        if ($this->exchange->updateCatalogExchangeRates()) {
            $this->auth->flash('success', 'Курси НБУ оновлено, ціни перераховано.');
        } else {
            $this->auth->flash('error', 'Не вдалося отримати курси НБУ.');
        }

        return $this->redirect(admin_url('rates'));
    }
}
