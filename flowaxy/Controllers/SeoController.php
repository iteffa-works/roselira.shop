<?php

declare(strict_types=1);

namespace Flowaxy\Controllers;

use Flowaxy\Core\Response;
use Flowaxy\Services\SitemapService;

final class SeoController
{
    public function __construct(private readonly SitemapService $sitemap)
    {
    }

    public function sitemap(): Response
    {
        return Response::xml($this->sitemap->buildXml());
    }
}
