<?php 

namespace App\Service\Exchange;

interface CurrencyExchangeInterface 
{
    /**
     * Currency converter.
     * Converts $amout using currency exchange rates. 
     * returns resulting amout.
     * 
     * @var string $from    The currency title to exchange from. Ex. "EUR".
     * @var string $to      The currency title to exchange to. Ex. "USD".
     */
    public function exchange(float $amount, string $from, string $to): float;
}
