<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Services\AdminAuthService;
use Flowaxy\Services\LocaleService;
use Flowaxy\Support\LocaleDefaults;

final class LocalesController extends AdminController
{
    public function __construct(
        View $view,
        AdminAuthService $auth,
        private readonly LocaleService $locale,
    ) {
        parent::__construct($view, $auth);
    }

    public function index(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        $editableLocales = $this->locale->editableLocales();
        $activeLocale = in_array($request->query('locale', ''), $editableLocales, true)
            ? (string) $request->query('locale')
            : $editableLocales[0];

        $content = $this->view->renderAdmin('locales', [
            'locales' => $editableLocales,
            'activeLocale' => $activeLocale,
            'strings' => array_merge(
                LocaleDefaults::all()[$activeLocale] ?? [],
                $this->locale->loadStrings($activeLocale),
            ),
            'enStrings' => array_merge(
                LocaleDefaults::en(),
                $this->locale->loadStrings('en'),
            ),
            'csrf' => $this->auth->csrfToken(),
        ]);

        return $this->renderPage($content, 'Тексти UI', 'locales');
    }

    public function save(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        if ($response = $this->verifyPost($request)) {
            return $response;
        }

        $locale = trim((string) $request->post('locale', ''));
        $editableLocales = $this->locale->editableLocales();

        if (in_array($locale, $editableLocales, true)) {
            $current = $this->locale->loadStrings($locale);
            $updated = [];

            foreach (array_keys($current) as $key) {
                $updated[$key] = trim((string) $request->post('key_' . $key, $current[$key] ?? ''));
            }

            if ($this->locale->saveStrings($locale, $updated)) {
                $this->auth->flash('success', "Тексти ($locale) збережено.");
            } else {
                $this->auth->flash('error', 'Помилка збереження.');
            }
        }

        return $this->redirect(admin_url('locales', ['locale' => $locale]));
    }
}
