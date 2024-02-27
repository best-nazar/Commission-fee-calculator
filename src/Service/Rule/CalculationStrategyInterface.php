<?php

namespace App\Service\Rule;

use App\Entity\UserOperation;

interface CalculationStrategyInterface 
{
    /**
     * Process the calculation of a fee amount over User's operation.
     * Commission fees are rounded up to currency's decimal places.
     * Returns formatterd string reprsenting original currency precision (10.00 or 10, etc.)
     */
    public function calculateFee(UserOperation $userOperation, array $feeRules): string;
}
