<?php

namespace App\Service\Rule;

use App\Entity\PrivateWithdraw;
use App\Entity\UserOperation;
use App\Service\Exchange\CurrencyExchangeInterface;

class PrivateWithdrawalStrategy implements CalculationStrategyInterface
{
    public const FEE_WITHDRAW_PRIVATE = 'withdraw_private';
    public const FEE_WITHDRAW_LIMIT = 'withdraw_private_limit';
    public const FEE_WITHDRAWALS_PER_WEEK = 'withdrawals_per_week';
    public const FEE_WITHDRAW_CURRENCY = 'withdraw_currency';

    protected string $withdrawCurrency;
    
    public function __construct(
        protected array &$weeklyWithdrawals, 
        protected CurrencyExchangeInterface $currencyExchange
    ){}
    
    public function calculateFee(UserOperation $op, array $feeRules): string
    {
        // Initialize free of charge limit
        $freeOfChargeLimit = $feeRules[self::FEE_WITHDRAW_LIMIT] ?? 0;
        $feeWeeks = $feeRules[self::FEE_WITHDRAWALS_PER_WEEK] ?? 0;
        $feeRate = $feeRules[self::FEE_WITHDRAW_PRIVATE] ?? 0;
        $this->withdrawCurrency = $feeRules[self::FEE_WITHDRAW_CURRENCY] ?? 'EUR';

        $week = $this->getPaymentWeek($op);

        // Check if it's the first withdrawal for the week
        if (!isset($this->weeklyWithdrawals[$op->getUid()][$week])) { 
            // the first operation in the week
            $pw = $this->newWithdrawal($op, $this->weeklyWithdrawals, $week, $freeOfChargeLimit);

            // we charge the fee only if the withdraw_private_limit is reached
            if ($pw->limitIsReached) {
                $toCharge = $pw->amount;
            } else {
                $toCharge = 0;
            }
        } else {
            // withddrawal has been issued this week
            $pw = $this->updateWithdrawal($op, $this->weeklyWithdrawals[$op->getUid()][$week], $feeWeeks, $freeOfChargeLimit);
            // more than one payment in the week
            $toCharge = $pw->amount;
        }

        $commission = $this->reverseExchange($toCharge, $op) * $feeRate / 100;

        return sprintf("%.{$op->getPrecision()}f", $commission);
    }

    /**
     * Returns the week range string in which the operation ocurred
     * assuming that a week begin is Monday.
     * Ex. "2014-12-29:2015-01-04"
     */
    protected function getPaymentWeek(UserOperation $op): string
    {
        // Get the day of the week (1 = Monday, 7 = Sunday)
        $dayOfWeek = $op->getDate()->format('N');
        // Calculate the difference between the current day and Monday
        $daysToMonday = $dayOfWeek - 1;
        // Calculate the difference between the current day and Sunday
        $daysToSunday = 7 - $dayOfWeek;
        // Calculate the Monday and Sunday dates
        $mondayDate = clone $op->getDate();
        $sundayDate = clone $op->getDate();

        $mondayDate->modify("-{$daysToMonday} days");
        $sundayDate->modify("+{$daysToSunday} days");

        // Format the dates
        $monday = $mondayDate->format('Y-m-d');
        $sunday = $sundayDate->format('Y-m-d');

        return $monday .':'. $sunday .'';
    }

    /**
     * Makes withdrawal amonut converted to the main currency {EUR}
     */
    protected function newWithdrawal(UserOperation $op, array &$collection, string $week, $freeOfChargeLimit): PrivateWithdraw
    {
        $pw = new PrivateWithdraw();
        $pw->count = 1;
        $amount = $this->exchageToFeeCurrency($op);

        if ($amount > $freeOfChargeLimit) {
            $pw->limitIsReached = true;
            $pw->amount = $amount - $freeOfChargeLimit;
        } else {
            $pw->amount = $amount;
            $pw->limitIsReached = false;
        }
    
        $collection[$op->getUid()][$week] = $pw;

        return $pw;
    }

    /**
     * Updates withdraw operation amount in the main currency {EUR}
     * Ex. 1000.00 EUR for a week (from Monday to Sunday) is free of charge. 
     * Only for the first 3 withdraw operations per a week. 
     * 4th and the following operations are calculated by using the rule above. 
     * If total free of charge amount is exceeded them commission is calculated 
     * only for the exceeded amount (i.e. up to 1000.00 EUR no commission fee is applied).
     */
    protected function updateWithdrawal(UserOperation $op, PrivateWithdraw $pw, int $weeks, float $threshold): PrivateWithdraw
    {
        $pw->count ++;
        // Stores amount in EUR
        $exAmount = $this->exchageToFeeCurrency($op);

        if ($pw->limitIsReached || $pw->count > $weeks) {
            $pw->amount = $exAmount;

            return $pw;
        }
    
        if (($pw->amount + $exAmount) > $threshold) {
            $pw->amount = $threshold + $pw->amount - $exAmount;
            $pw->limitIsReached = true;
        }

        return $pw;
    }

    /**
     * If withdrawal operation currency is not in the main fee currency 
     * we have to conver the amount within exchange rate.
     */
    private function exchageToFeeCurrency(UserOperation $op): float
    {
        $amount = 0 ;

        if ($op->getCurrency() === $this->withdrawCurrency) {
            $amount = $op->getAmount();
        } else {
            $amount = $this->currencyExchange->exchange($op->getAmount(), $op->getCurrency(), $this->withdrawCurrency);
        }

        return $amount;
    }

    /**
     * Convert amount in a main currency to the original withdraw currency.
     * Commission fee is always calculated in the currency of the operation.
     */
    private function reverseExchange(float $amount, UserOperation $op): float
    {
        if ($op->getCurrency() === $this->withdrawCurrency) {
            return $amount;
        }
            
        $amount = $this->currencyExchange->exchange($amount, $this->withdrawCurrency, $op->getCurrency());
        
        return $amount;
    }
}