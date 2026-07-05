<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Services\AdminAuthService;
use Flowaxy\Services\LegalPageService;

final class PagesController extends AdminController
{
    public function __construct(
        View $view,
        AdminAuthService $auth,
        private readonly LegalPageService $legalPages,
    ) {
        parent::__construct($view, $auth);
    }

    public function index(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        $activePage = $this->resolvePage((string) $request->query('page', ''));
        $activeLocale = $this->resolveLocale((string) $request->query('locale', ''));

        $content = $this->view->renderAdmin('pages', [
            'pages' => $this->legalPages->pageLabels(),
            'locales' => LegalPageService::LOCALES,
            'activePage' => $activePage,
            'activeLocale' => $activeLocale,
            'sections' => $this->legalPages->getSections($activePage, $activeLocale),
            'previewUrl' => '/' . $activePage,
            'csrf' => $this->auth->csrfToken(),
        ]);

        return $this->renderPage($content, 'Сторінки', 'pages');
    }

    public function save(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        if ($response = $this->verifyPost($request)) {
            return $response;
        }

        $page = $this->resolvePage((string) $request->post('page', ''));
        $locale = $this->resolveLocale((string) $request->post('locale', ''));
        $action = trim((string) $request->post('action', 'save'));

        if ($action === 'reset') {
            $saved = $this->legalPages->resetSections($page, $locale);
            $this->auth->flash(
                $saved ? 'success' : 'error',
                $saved ? 'Текст сторінки скинуто до стандартного.' : 'Не вдалося скинути.',
            );
        } else {
            /** @var list<array{heading?: string, paragraphs?: string}> $sections */
            $sections = $request->post('sections', []);
            if (!is_array($sections)) {
                $sections = [];
            }

            $saved = $this->legalPages->saveSections($page, $locale, array_values($sections));

            $this->auth->flash(
                $saved ? 'success' : 'error',
                $saved ? 'Сторінку збережено.' : 'Не вдалося зберегти. Перевірте текст секцій.',
            );
        }

        return $this->redirect(admin_url('pages', ['page' => $page, 'locale' => $locale]));
    }

    private function resolvePage(string $page): string
    {
        return $this->legalPages->isValidPage($page) ? $page : LegalPageService::PAGES[0];
    }

    private function resolveLocale(string $locale): string
    {
        return $this->legalPages->isValidLocale($locale) ? $locale : LegalPageService::LOCALES[0];
    }
}
