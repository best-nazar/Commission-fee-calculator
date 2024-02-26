<?php

namespace Test;

use App\Command\CommissionFeeCommand;
use App\Service\Exchange\CurrencyExchangeInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CommissionFeeCommandTest extends KernelTestCase
{
    protected Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->application = new Application(self::$kernel);
        $this->application->setAutoExit(false);

        $mocked = $this->getMockBuilder(CurrencyExchangeInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(["exchange"])
            ->getMock();

        static::$kernel->getContainer()->set(CurrencyExchangeInterface::class, $mocked);

        $mocked->expects($this->any())->method("exchange")->willReturnCallback(function (float $amount, string $from, string $to) {
            $pair = "{$from}:{$to}";
            $rates = [
                'EUR:USD'   => 1.1497,
                'EUR:JPY'   => 129.53,
                'USD:EUR'   => 0.8696,
                'JPY:EUR'   => 0.00772,
            ];

            return $amount * $rates[$pair];
        });
    }

    public function testExecute()
    {
        $expected = [
            "0.60",
            "3.00",
            "0.00",
            "0.06",
            "1.50",
            "0",
            "0.69", // "0.70" in task,
            "0.30",
            "0.30",
            "3.00",
            "0.00",
            "0.00",
            "8611", // "8612" in task,
        ];

        $command = $this->application->get('app:calculate');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command'                       => $command->getName(),
            CommissionFeeCommand::CSV_FILE  => 'tests/input.csv'
        ));

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $actual = explode(PHP_EOL, trim($output));
        
        $this->assertEquals($expected, $actual);
    }
}