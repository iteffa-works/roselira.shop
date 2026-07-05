<?php

declare(strict_types=1);

namespace Flowaxy\Services;

final class ExchangeService
{
    public function __construct(private readonly CatalogService $catalog)
    {
    }

    public function getRates(): array
    {
        return $this->catalog->getExchangeRatesMeta();
    }

    public function fetchNbuRates(): ?array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'header' => "User-Agent: RoseliraFlowaxy/1.0\r\n",
            ],
        ]);

        $raw = @file_get_contents(
            'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json',
            false,
            $context,
        );

        if ($raw === false) {
            return null;
        }

        $items = json_decode($raw, true);
        if (!is_array($items)) {
            return null;
        }

        $rates = ['source' => 'NBU', 'date' => date('Y-m-d'), 'EUR' => 0.0, 'USD' => 0.0];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $code = (string) ($item['cc'] ?? '');
            if ($code === 'EUR' || $code === 'USD') {
                $rates[$code] = (float) ($item['rate'] ?? 0);
            }
        }

        if ($rates['EUR'] <= 0 || $rates['USD'] <= 0) {
            return null;
        }

        return $rates;
    }

    public function convertToUah(float $amount, string $currency, ?array $rates = null): int
    {
        $rates ??= $this->getRates();
        $currency = strtoupper($currency);

        if ($currency === 'UAH') {
            return (int) round($amount);
        }

        $rate = (float) ($rates[$currency] ?? 0);
        if ($rate <= 0) {
            return (int) round($amount);
        }

        return (int) round($amount * $rate);
    }

    public function recalculateCatalogPrices(array $catalog, ?array $rates = null): array
    {
        $rates ??= $this->getRates();

        foreach ($catalog['products'] ?? [] as &$product) {
            if (!is_array($product)) {
                continue;
            }

            foreach ($product['variants'] ?? [] as &$variant) {
                if (is_array($variant)) {
                    $this->applyVariantPricing($variant, $rates);
                }
            }
            unset($variant);

            $product['price_currency'] = 'UAH';
        }
        unset($product);

        return $catalog;
    }

    public function updateCatalogExchangeRates(?array $rates = null): bool
    {
        $rates ??= $this->fetchNbuRates();
        if ($rates === null) {
            return false;
        }

        $catalog = $this->catalog->loadCatalog();
        $catalog['meta'] ??= [];
        $catalog['meta']['exchange_rates'] = $rates;
        $catalog = $this->recalculateCatalogPrices($catalog, $rates);

        return $this->catalog->saveCatalog($catalog);
    }

    private function applyVariantPricing(array &$variant, array $rates): void
    {
        if (isset($variant['price_eur']) && (float) $variant['price_eur'] > 0) {
            $variant['price'] = $this->convertToUah((float) $variant['price_eur'], 'EUR', $rates);
            $variant['price_currency'] = 'UAH';
        } elseif (isset($variant['price_usd']) && (float) $variant['price_usd'] > 0) {
            $variant['price'] = $this->convertToUah((float) $variant['price_usd'], 'USD', $rates);
            $variant['price_currency'] = 'UAH';
        }

        if (isset($variant['price_old_eur']) && (float) $variant['price_old_eur'] > 0) {
            $variant['price_old'] = $this->convertToUah((float) $variant['price_old_eur'], 'EUR', $rates);
        } elseif (isset($variant['price_old_usd']) && (float) $variant['price_old_usd'] > 0) {
            $variant['price_old'] = $this->convertToUah((float) $variant['price_old_usd'], 'USD', $rates);
        }
    }
}
