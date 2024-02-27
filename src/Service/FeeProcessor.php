<?php

namespace App\Service;

use App\Service\Exchange\CurrencyExchangeInterface;
use App\Service\Rule\BusinessWithdrawalStrategy;
use App\Service\Rule\CalculationStrategyInterface;
use App\Service\Rule\DepositStrategy;
use App\Service\Rule\PrivateWithdrawalStrategy;
use App\Entity\UserOperation;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class FeeProcessor
{
    public const OP_TYPE_WITHDRAW = 'withdraw';
    public const OP_TYPE_DEPOSIT = 'deposit';
    public const OP_WITHDRAW_PRIVATE = 'private';

    /**
     * Storage for user's weekly withdrawals
     */
    protected array $weeklyWithdrawals = [];

    private CalculationStrategyInterface $calculationStrategy;

    public function __construct(
        #[Autowire('%app.fee_rules%')]
        private array $feeRules,
        private DataLoaderInterface $dataLoader,
        private CurrencyExchangeInterface $currencyExchange,
    ){}

    /**
     * Set Data source for the Dataloader.
     */
    public function setSource(string $sourcePath)
    {
        $this->dataLoader->setSourcePath($sourcePath);
    }

    /**
     * Set/Change Calculation strategy on the runtime.
     */
    public function setCalculationStrategy(CalculationStrategyInterface $strategy) 
    {
        $this->calculationStrategy = $strategy;
    }  

    /**
     * Process the calculation of the few over the data source.
     */
    public function calculateFee(): array
    {
        $result = [];
        $data= $this->dataLoader->load();

        /** @var UserOperation $item  */
        foreach ($data as $item) {
            $result[] = $this->processPayment($item);
        }

        return $result;
    }

    /**
     * Process payment operation based on its type.
     */
    public function processPayment(UserOperation $item): string
    {
        switch ($item->getOpType()) {
            case self::OP_TYPE_WITHDRAW:
                // Use the appropriate strategy based on client type
                if ($item->getClientType() === self::OP_WITHDRAW_PRIVATE) {
                    $this->setCalculationStrategy(new PrivateWithdrawalStrategy(
                        $this->weeklyWithdrawals,
                        $this->currencyExchange
                    ));
                } else {
                    $this->setCalculationStrategy(new BusinessWithdrawalStrategy());
                }
                break;

            case self::OP_TYPE_DEPOSIT:
                // Use the deposit strategy
                $this->setCalculationStrategy(new DepositStrategy());
                break;

            default:
                throw new \InvalidArgumentException(
                    "Invalid operation type " + $item->getOpType() ." for UID:" . $item->getUid()
                );
        }

        // Perform the calculation using the selected strategy
        return $this->calculationStrategy->calculateFee($item, $this->feeRules);
    }
}
