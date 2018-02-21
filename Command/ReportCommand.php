<?php

namespace Loevgaard\DandomainConsignmentBundle\Command;

use Loevgaard\DandomainConsignmentBundle\ConsignmentService\ConsignmentServiceCollection;
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
            ->addOption('deliver', null, InputOption::VALUE_REQUIRED, 'If set the command will deliver the report. Default: true', true)
            ->addOption('start', null, InputOption::VALUE_REQUIRED, 'The start date in the format `YYYY-MM-DD`')
            ->addOption('end', null, InputOption::VALUE_REQUIRED, 'The end date in the format `YYYY-MM-DD`')
            ->addOption('update-last-stock-movement', null, InputOption::VALUE_REQUIRED, 'If set, the command will update the last stock movement property for the manufacturer', true)
            ->addOption('use-last-stock-movement', null, InputOption::VALUE_REQUIRED, 'If set, the command will use the last stock movement as the starting point when generating the report', true)
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Loevgaard\DandomainConsignmentBundle\Exception\NonExistentConsignmentServiceException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // fetch arguments and options
        $manufacturer = $input->getArgument('manufacturer');
        $deliver = $input->getOption('deliver') === true || $input->getOption('deliver') === 'true';
        $updateLastStockMovement = $input->getOption('update-last-stock-movement') === true || $input->getOption('update-last-stock-movement') === 'true';
        $useLastStockMovement = $input->getOption('use-last-stock-movement') === true || $input->getOption('use-last-stock-movement') === 'true';
        $start = $input->getOption('start');
        $end = $input->getOption('end');

        // validate dates
        if($start) {
            $start = \DateTime::createFromFormat('Y-m-d', $start);
            if ($start === false) {
                throw new \InvalidArgumentException('The format for start is invalid');
            }
        }

        if($end) {
            $end = \DateTime::createFromFormat('Y-m-d', $end);
            if ($end === false) {
                throw new \InvalidArgumentException('The format for end is invalid');
            }
        }

        // find manufacturer
        $manufacturer = $this->manufacturerRepository->findOneByExternalId($manufacturer);

        if(!$manufacturer) {
            throw new \InvalidArgumentException('The manufacturer does not exist');
        }

        // check if the manufacturer is enabled for consignment
        if(!$manufacturer->isConsignment()) {
            throw new \InvalidArgumentException('Consignment is not enabled for the manufacturer');
        }

        if($input->isInteractive()) {
            // output config
            $table = new Table($output);
            $table
                ->setHeaders(['Option', 'Value'])
                ->setRows([
                    ['Manufacturer', $manufacturer->getName()],
                    ['Delivery', $deliver ? 'Yes' : 'No'],
                    ['Update last stock movement', $updateLastStockMovement ? 'Yes' : 'No'],
                    ['Use last stock movement', $useLastStockMovement ? 'Yes' : 'No'],
                    ['Start date', $start ? $start->format('Y-m-d') : 'None'],
                    ['End date', $end ? $end->format('Y-m-d') : 'None'],
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

        // generate the report
        $report = $consignmentService->generateReport([
            'update_last_stock_movement' => $updateLastStockMovement,
            'use_last_stock_movement' => $useLastStockMovement,
            'start_date' => $start,
            'end_date' => $end
        ]);

        // generate report file because we want to generate the file no matter if the $deliver option is set
        $consignmentService->generateReportFile($report);

        // deliver report
        if($deliver) {
            $consignmentService->deliverReport($report);
        }
    }
}
