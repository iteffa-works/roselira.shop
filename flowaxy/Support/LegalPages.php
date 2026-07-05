<?php

declare(strict_types=1);

namespace Flowaxy\Support;

final class LegalPages
{
    /** @var list<string> */
    public const KEYS = ['privacy', 'terms', 'delivery'];

    /** @var array<string, array{title: string, desc: string}> */
    public const META = [
        'privacy' => ['title' => 'meta_privacy_title', 'desc' => 'meta_privacy_desc'],
        'terms' => ['title' => 'meta_terms_title', 'desc' => 'meta_terms_desc'],
        'delivery' => ['title' => 'meta_delivery_title', 'desc' => 'meta_delivery_desc'],
    ];
}
