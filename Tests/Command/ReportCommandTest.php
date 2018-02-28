<?php

namespace Loevgaard\DandomainConsignmentBundle\Tests\Command;

use Loevgaard\DandomainConsignmentBundle\Command\ReportCommand;
use Loevgaard\DandomainConsignmentBundle\ConsignmentService\ConsignmentServiceCollection;
use Loevgaard\DandomainFoundation\Repository\ManufacturerRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ReportCommandTest extends TestCase
{
    public function testExpectNonExistentManufacturer()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The manufacturer does not exist');

        $command = $this->getCommand();
        $this->execute($command);
    }

    public function testExpectWrongStartDateFormat()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The format for start is invalid');

        $command = $this->getCommand();
        $this->execute($command, 'm', '20180101');
    }

    public function testExpectWrongEndDateFormat()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The format for end is invalid');

        $command = $this->getCommand();
        $this->execute($command, 'm', null, '20180101');
    }

    private function getCommand($container = null): ReportCommand
    {
        if (!$container) {
            $container = $this->getContainer();
        }

        /** @var \PHPUnit_Framework_MockObject_MockObject|ManufacturerRepository $manufacturerRepository */
        $manufacturerRepository = $this->getMockBuilder(ManufacturerRepository::class)->disableOriginalConstructor()->getMock();

        $consignmentServiceCollection = new ConsignmentServiceCollection();

        $command = new ReportCommand($manufacturerRepository, $consignmentServiceCollection);
        $command->setContainer($container);

        return $command;
    }

    private function execute(ReportCommand $command, $manufacturer = 'manufacturer', $start = null, $end = null): CommandTester
    {
        $application = new Application();
        $application->setAutoExit(false);
        $application->add($command);

        $input = [
            'command' => $command->getName(),
            'manufacturer' => $manufacturer,
        ];

        if ($start) {
            $input['--start'] = $start;
        }

        if ($end) {
            $input['--end'] = $end;
        }

        $command = $application->find('loevgaard:dandomain-consignment:report');
        $commandTester = new CommandTester($command);
        $commandTester->execute($input);

        return $commandTester;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    private function getContainer()
    {
        return $this->createMock(ContainerInterface::class);
    }
}
