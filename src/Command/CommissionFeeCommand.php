<?php

namespace App\Command;

use App\Service\FeeProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'app:calculate',
    description: 'Commission fee calculation',
)]
class CommissionFeeCommand extends Command
{   
    public const CSV_FILE = 'csv_file';

    public function __construct(
        private FeeProcessor $service,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(self::CSV_FILE, InputArgument::REQUIRED, 'Path to .csv file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $arg1 = $input->getArgument(self::CSV_FILE);
        $this->service->setSource($arg1);

        $result = $this->service->calculateFee();

        $output->writeln($result);

        return Command::SUCCESS;
    }
}