<?php

declare(strict_types=1);

namespace Flowaxy\Controllers;

use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Services\LocaleService;
use Flowaxy\Support\LegalContent;

final class PageController
{
    /** @var array<string, array{title: string, desc: string}> */
    private const PAGES = [
        'privacy' => ['title' => 'meta_privacy_title', 'desc' => 'meta_privacy_desc'],
        'terms' => ['title' => 'meta_terms_title', 'desc' => 'meta_terms_desc'],
        'delivery' => ['title' => 'meta_delivery_title', 'desc' => 'meta_delivery_desc'],
    ];

    public function __construct(
        private readonly View $view,
        private readonly LocaleService $locale,
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
        $meta = self::PAGES[$page] ?? self::PAGES['privacy'];
        $locale = $this->locale->current();

        return Response::html($this->view->render('layout', [
            'locale' => $locale,
            'title' => $this->locale->t($meta['title']),
            'description' => $this->locale->t($meta['desc']),
            'canonicalPath' => '/' . $page,
            'content' => 'legal/page',
            'pageHeading' => $this->locale->t($meta['title']),
            'legalSections' => LegalContent::sections($page, $locale),
        ]));
    }
}
