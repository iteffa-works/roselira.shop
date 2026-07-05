<?php

declare(strict_types=1);

namespace Flowaxy\Controllers;

use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Services\LegalPageService;
use Flowaxy\Services\LocaleService;
use Flowaxy\Support\LegalPages;

final class PageController
{
    public function __construct(
        private readonly View $view,
        private readonly LocaleService $locale,
        private readonly LegalPageService $legalPages,
    ) {
    }

    public function privacy(): Response
    {
        return $this->render('privacy');
    }

    public function terms(): Response
    {
        return $this->render('terms');
    }

    public function delivery(): Response
    {
        return $this->render('delivery');
    }

    private function render(string $page): Response
    {
        $meta = LegalPages::META[$page] ?? LegalPages::META['privacy'];
        $locale = $this->locale->current();

        return Response::html($this->view->render('layout', [
            'locale' => $locale,
            'title' => $this->locale->t($meta['title']),
            'description' => $this->locale->t($meta['desc']),
            'canonicalPath' => '/' . $page,
            'content' => 'legal/page',
            'pageHeading' => $this->locale->t($meta['title']),
            'legalSections' => $this->legalPages->getSections($page, $locale),
        ]));
    }
}
