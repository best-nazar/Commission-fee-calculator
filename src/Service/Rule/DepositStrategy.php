<?php 

namespace App\Service\Rule;

use App\Entity\UserOperation;

class DepositStrategy implements CalculationStrategyInterface
{
    use IntFloatAmountTrait;

    public const FEE_DEPOSIT = 'deposit';
    
    public function calculateFee(UserOperation $operation, array $feeRules): string
    {
        $depositFee = $feeRules[self::FEE_DEPOSIT] ?? 0 ;
        $fee = $depositFee/100; // as %

        $feeFloat = $this->intFloatAmount($operation->getAmount(), $fee, $operation->getPrecision());

        return sprintf("%.{$operation->getPrecision()}f", $feeFloat);
    }
}
