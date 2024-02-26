<?php

namespace App\Service\Rule;

use App\Entity\UserOperation;

class BusinessWithdrawalStrategy implements CalculationStrategyInterface
{
    use IntFloatAmountTrait;

    public const FEE_BUSINESS = 'withdraw_business';

    public function calculateFee(UserOperation $operation, array $feeRules): string
    {
        $businessFee = $feeRules[self::FEE_BUSINESS] ?? 0;
        $fee = $businessFee / 100; // as %
        $feeFloat = $this->intFloatAmount($operation->getAmount(), $fee, $operation->getPrecision());

        return sprintf("%.{$operation->getPrecision()}f", $feeFloat);
    }
}