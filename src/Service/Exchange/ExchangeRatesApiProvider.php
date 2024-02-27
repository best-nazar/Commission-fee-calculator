<?php

namespace App\Service\Exchange;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExchangeRatesApiProvider implements CurrencyExchangeInterface
{
    public const API_BASE_URL = 'api_base_URL';
    public const API_KEY = 'apikey';
    public const API_BASE = '{base}';
    public const API_SYMBOLS = '{symbols}';
    public const API_CURRENCIES = 'currencies';
    public const API_MAIN_CURRENCY = 'main_currency';

    private array $rateCache = [];
    
    public function __construct(
        #[Autowire('%app.exchange%')]
        private array $apiConfig,
        private HttpClientInterface $client
    ){}

    public function exchange(float $amount, string $from, string $to): float
    {
        $currRate = $this->getRate($from, $to);

        return $amount * $currRate;
    }

    public function getRate(string $from, string $to): float
    {
        if (!$this->hasRates()) {
            $data= $this->fetchLatest($this->apiConfig[self::API_MAIN_CURRENCY], $this->apiConfig[self::API_CURRENCIES]);
            $this->cacheRates($data);
        }

        $pair = "{$from}:{$to}";

        return $this->rateCache[$pair];
    }

    public function fetchLatest(string $base, string $currencies)
    {
        $client = $this->client->withOptions([
            'base_uri'  => $this->apiConfig[self::API_BASE_URL],
            'headers'   => [self::API_KEY => $this->apiConfig[self::API_KEY]],
        ]);

        $response = $client->request('GET', $this->getApiUrl($base, $currencies));

        if ($response->getStatusCode() !== 200) {
            throw new \ErrorException(sprintf('ExchangeRateApiProvider failed to retrieve exchange rates, code %s', $response->getStatusCode()));
        }

        return json_decode($response->getContent(), true);
    }

    /**
     * Build API URL withh required parameters, like
     * // https://api.apilayer.com/exchangerates_data/latest?symbols={symbols}&base={base}
     */
    public function getApiUrl(string $base, string $currencies): string
    {        
        return str_replace([self::API_BASE, self::API_SYMBOLS], [$base, $currencies], '?symbols={symbols}&base={base}');
    }

    protected function cacheRates(array $rates): void
    {
        if (!isset($rates['rates'])) {
            throw new \ErrorException('ExchangeRateApiProvider failed to retrieve rates from response');
        }

        foreach ($rates['rates'] as $curr => $value) {
            $from = $this->apiConfig[self::API_MAIN_CURRENCY];
            $pair = "{$from}:{$curr}";
            $reversePair = "{$curr}:{$from}";

            $this->rateCache[$pair] = $value;
            $this->rateCache[$reversePair] = 1/$value;
        }
    }

    protected function hasRates(): bool
    {
        $required = $this->generateCurrencyCombinations($this->apiConfig[self::API_MAIN_CURRENCY], $this->apiConfig[self::API_CURRENCIES]);

        if (count(array_intersect_key(array_flip($required), $this->rateCache)) === count($required)) {
            // All required keys (currencies) exist!
            return true;
        }

        return false;
    }

    /**
     * Generates currency pairs based on app configuration required for fee calculation
     * returns array of keys.
     * Ex.: [
     *    EUR:USD
     *    USD:EUR
     *    EUR:JPY
     *    JPY:EUR
     * ]
     */
    private function generateCurrencyCombinations($baseCurrency, $currencies)
    {
        $combinations = [];
        $currList = explode(",", $currencies);
    
        foreach ($currList as $currency) {
            if ($currency !== $baseCurrency) {
                // Combine base currency with each other currency
                $combination1 = $baseCurrency . ':' . $currency;
                $combination2 = $currency . ':' . $baseCurrency;
    
                $combinations[] = $combination1;
                $combinations[] = $combination2;
            }
        }
    
        return $combinations;
    }
}
