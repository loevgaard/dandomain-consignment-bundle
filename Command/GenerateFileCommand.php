<?php

declare(strict_types=1);

namespace Loevgaard\DandomainConsignmentBundle\Command;

use Loevgaard\DandomainConsignment\Entity\Generated\ReportInterface;
use Loevgaard\DandomainConsignment\Repository\ReportRepository;
use Loevgaard\DandomainConsignmentBundle\ConsignmentService\ConsignmentServiceCollection;
use Loevgaard\DandomainConsignmentBundle\Exception\NonExistentConsignmentServiceException;
use Loevgaard\DandomainConsignmentBundle\Exception\NonExistentReportException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateFileCommand extends ContainerAwareCommand
{
    /**
     * @var ReportRepository
     */
    private $reportRepository;

    /**
     * @var ConsignmentServiceCollection
     */
    private $consignmentServiceCollection;

    public function __construct(ReportRepository $reportRepository, ConsignmentServiceCollection $consignmentServiceCollection)
    {
        parent::__construct();

        $this->reportRepository = $reportRepository;
        $this->consignmentServiceCollection = $consignmentServiceCollection;
    }

    protected function configure()
    {
        $this
            ->setName('loevgaard:dandomain-consignment:generate-file')
            ->setDescription('Generates a report file from an existing report')
            ->addArgument('report', InputArgument::REQUIRED, 'The report to generate')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     *
     * @throws NonExistentReportException
     * @throws NonExistentConsignmentServiceException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $reportId = (int) $input->getArgument('report');

        /** @var ReportInterface $report */
        $report = $this->reportRepository->find($reportId);

        if (!$report) {
            throw new NonExistentReportException('The report with id '.$reportId.' does not exist');
        }

        // find the consignment service
        $consignmentService = $this->consignmentServiceCollection->findConsignmentService($report->getManufacturer());
        $consignmentService->setLogger(new ConsoleLogger($output));

        // generate the report
        $reportFile = $consignmentService->generateReportFile($report);

        $output->writeln('Report saved to '.$reportFile->getPathname());
    }
}
