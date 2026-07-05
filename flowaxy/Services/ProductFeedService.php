<?php

declare(strict_types=1);

namespace Flowaxy\Services;

final class ProductFeedService
{
    public function __construct(private readonly CatalogService $catalog)
    {
    }

    /** @return list<array{id: string, title: string, description: string, link: string, image: string, price: string, availability: string, brand: string}> */
    public function getItems(?string $locale = null): array
    {
        return $this->catalog->getFeedItems($locale ?? 'uk');
    }

    public function buildGoogleXml(?string $locale = null): string
    {
        return $this->buildXml($this->getItems($locale));
    }

    public function buildMetaXml(?string $locale = null): string
    {
        return $this->buildXml($this->getItems($locale));
    }

    /** @param list<array{id: string, title: string, description: string, link: string, image: string, price: string, availability: string, brand: string}> $items */
    private function buildXml(array $items): string
    {
        $base = htmlspecialchars(rtrim(app_url(), '/'), ENT_XML1);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
        $xml .= '<channel><title>Roselira</title><link>' . $base . '</link>' . "\n";

        foreach ($items as $item) {
            $xml .= '<item>';
            $xml .= '<g:id>' . htmlspecialchars($item['id'], ENT_XML1) . '</g:id>';
            $xml .= '<g:title>' . htmlspecialchars($item['title'], ENT_XML1) . '</g:title>';
            $xml .= '<g:description>' . htmlspecialchars($item['description'], ENT_XML1) . '</g:description>';
            $xml .= '<g:link>' . htmlspecialchars($item['link'], ENT_XML1) . '</g:link>';
            $xml .= '<g:image_link>' . htmlspecialchars($item['image'], ENT_XML1) . '</g:image_link>';
            $xml .= '<g:availability>' . htmlspecialchars($item['availability'], ENT_XML1) . '</g:availability>';
            $xml .= '<g:price>' . htmlspecialchars($item['price'], ENT_XML1) . '</g:price>';
            $xml .= '<g:brand>' . htmlspecialchars($item['brand'], ENT_XML1) . '</g:brand>';
            $xml .= '<g:condition>new</g:condition>';
            $xml .= '</item>' . "\n";
        }

        $xml .= '</channel></rss>';

        return $xml;
    }
}
