<?php 

namespace App\Service\Rule;

trait IntFloatAmountTrait {
    /**
     * Float cannot always represent exact decimal fractions. 
     * If you want exact decimal values, you're better off using integers and 
     * then dividing by the precision you want at the end.
     * For example, if you're doing calculations in floats represeting Dollars
     * you may want to actually do your calculations in integer cents.
     * "Integer cents" means that, for example, the amount $24.95 should be stored 
     * not as a floating-point 24.95, but rather as the integer 2495. 
     * The amount $10.00 would be stored as the integer 1000.
     */
    public function intFloatAmount(float $sum, float $fee, int $presicion): float
    {
        // if we have presicion 2 ("X.00" 2 decimals after ) then we have 
        // to multiply the number by 100 to present a sum as integer
        $multiplier = pow(10, $presicion);

        //the amount $24.95 should be stored not as a floating-point 24.95, but rather as the integer 2495.
        $amount = $sum * $multiplier;
        $feeInt = $amount * $fee;
        // deviding the number we convert it to float back
        $feeFloat = $feeInt / $multiplier ;

        return $feeFloat;
    }
}