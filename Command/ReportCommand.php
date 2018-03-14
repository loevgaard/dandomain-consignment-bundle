<?php

namespace Loevgaard\DandomainConsignmentBundle\Command;

use Loevgaard\DandomainConsignmentBundle\ConsignmentService\ConsignmentServiceCollection;
use Loevgaard\DandomainConsignmentBundle\Exception\ConsignmentNotEnabledException;
use Loevgaard\DandomainConsignmentBundle\Exception\InvalidDateFormatException;
use Loevgaard\DandomainConsignmentBundle\Exception\NonExistentConsignmentServiceException;
use Loevgaard\DandomainConsignmentBundle\Exception\NonExistentManufacturerException;
use Loevgaard\DandomainFoundation\Repository\ManufacturerRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ReportCommand extends ContainerAwareCommand
{
    /**
     * @var ManufacturerRepository
     */
    protected $manufacturerRepository;

    /**
     * @var ConsignmentServiceCollection
     */
    protected $consignmentServiceCollection;

    public function __construct(ManufacturerRepository $manufacturerRepository, ConsignmentServiceCollection $consignmentServiceCollection)
    {
        $this->manufacturerRepository = $manufacturerRepository;
        $this->consignmentServiceCollection = $consignmentServiceCollection;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('loevgaard:dandomain-consignment:report')
            ->setDescription('Generates a report and optionally delivers it to the given manufacturer')
            ->addArgument('manufacturer', InputArgument::REQUIRED, 'The manufacturer to generate a report for. Use the id from Dandomain')
            ->addOption('start', null, InputOption::VALUE_REQUIRED, 'The start date in the format `YYYY-MM-DD`')
            ->addOption('end', null, InputOption::VALUE_REQUIRED, 'The end date in the format `YYYY-MM-DD`')
            ->addOption('do-not-deliver', null, InputOption::VALUE_NONE, 'If set the command will NOT deliver the report')
            ->addOption('do-not-update-last-stock-movement', null, InputOption::VALUE_NONE, 'If set, the command will NOT update the last stock movement property for the manufacturer')
            ->addOption('do-not-use-last-stock-movement', null, InputOption::VALUE_NONE, 'If set, the command will NOT use the last stock movement as the starting point when generating the report')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     *
     * @throws ConsignmentNotEnabledException
     * @throws InvalidDateFormatException
     * @throws NonExistentConsignmentServiceException
     * @throws NonExistentManufacturerException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // fetch arguments and options
        $manufacturer = $input->getArgument('manufacturer');
        $start = $input->getOption('start');
        $end = $input->getOption('end');
        $doNotDeliver = boolval($input->getOption('do-not-deliver'));
        $doNotUpdateLastStockMovement = boolval($input->getOption('do-not-update-last-stock-movement'));
        $doNotUseLastStockMovement = boolval($input->getOption('do-not-use-last-stock-movement'));

        // validate dates
        if ($start) {
            $start = \DateTime::createFromFormat('Y-m-d', $start);
            if (false === $start) {
                throw new InvalidDateFormatException('The format for start is invalid');
            }
        }

        if ($end) {
            $end = \DateTime::createFromFormat('Y-m-d', $end);
            if (false === $end) {
                throw new InvalidDateFormatException('The format for end is invalid');
            }
        }

        // find manufacturer
        $manufacturer = $this->manufacturerRepository->findOneByExternalId($manufacturer);

        if (!$manufacturer) {
            throw new NonExistentManufacturerException('The manufacturer does not exist');
        }

        // check if the manufacturer is enabled for consignment
        if (!$manufacturer->isConsignment()) {
            throw new ConsignmentNotEnabledException('Consignment is not enabled for the manufacturer');
        }

        if ($input->isInteractive()) {
            // output config
            $table = new Table($output);
            $table
                ->setHeaders(['Option', 'Value'])
                ->setRows([
                    ['Manufacturer', $manufacturer->getName()],
                    ['Start date', $start ? $start->format('Y-m-d') : 'None'],
                    ['End date', $end ? $end->format('Y-m-d') : 'None'],
                    ['Deliver?', $doNotDeliver ? 'No' : 'Yes'],
                    ['Update last stock movement?', $doNotUpdateLastStockMovement ? 'No' : 'Yes'],
                    ['Use last stock movement?', $doNotUseLastStockMovement ? 'No' : 'Yes'],
                ]);
            $table->render();

            // confirm config
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue with this config? ', false);

            if (!$helper->ask($input, $output, $question)) {
                return;
            }
        }

        // find the consignment service
        $consignmentService = $this->consignmentServiceCollection->findConsignmentService($manufacturer);
        $consignmentService->setLogger(new ConsoleLogger($output));

        $options = [
            'update_last_stock_movement' => !$doNotUpdateLastStockMovement,
            'use_last_stock_movement' => !$doNotUseLastStockMovement,
        ];

        if($start) {
            $options['start_date'] = $start;
        }

        if($end) {
            $options['end_date'] = $end;
        }

        // generate the report
        $report = $consignmentService->generateReport($options);

        // generate report file because we want to generate the file no matter if the $deliver option is set
        $consignmentService->generateReportFile($report);

        // deliver report
        if (!$doNotDeliver) {
            $consignmentService->deliverReport($report);
        }
    }
}
