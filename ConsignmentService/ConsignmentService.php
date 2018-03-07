<?php

declare(strict_types=1);

namespace Loevgaard\DandomainConsignmentBundle\ConsignmentService;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Loevgaard\DandomainConsignment\Entity\Generated\ReportInterface;
use Loevgaard\DandomainConsignment\Entity\Report;
use Loevgaard\DandomainConsignment\Repository\ReportRepository;
use Loevgaard\DandomainConsignmentBundle\Exception\InvalidBarCodeException;
use Loevgaard\DandomainConsignmentBundle\Exception\InvalidVendorNumberException;
use Loevgaard\DandomainFoundation\Entity\Generated\ManufacturerInterface;
use Loevgaard\DandomainStock\Entity\Generated\StockMovementInterface;
use Loevgaard\DandomainStock\Entity\StockMovement;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class ConsignmentService implements ConsignmentServiceInterface
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ReportRepository
     */
    protected $reportRepository;

    /**
     * The directory where report files will be saved.
     *
     * @var string
     */
    protected $reportDir;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ManufacturerInterface
     */
    protected $manufacturer;

    /**
     * Contains the included product ids.
     *
     * @var array
     */
    protected $includedProductIds;

    /**
     * Contains the excluded product ids.
     *
     * @var array
     */
    protected $excludedProductIds;

    public function __construct(ManagerRegistry $managerRegistry, ReportRepository $reportRepository, string $reportDir)
    {
        $this->entityManager = $managerRegistry->getManager();
        $this->reportRepository = $reportRepository;
        $this->reportDir = rtrim($reportDir, '/');
        $this->logger = new NullLogger();

        if (!is_dir($this->reportDir)) {
            throw new \InvalidArgumentException('The report dir given is not a directory');
        }

        if (!is_writable($this->reportDir)) {
            throw new \InvalidArgumentException('The report dir given is not writable');
        }
    }

    /**
     * @param array $options
     *
     * @return ReportInterface
     *
     * @throws ORMException
     */
    public function generateReport(array $options = []): ReportInterface
    {
        // resolve options
        $resolver = new OptionsResolver();
        $this->configureGenerateReportOptions($resolver);
        $options = $resolver->resolve($options);

        $report = new Report();
        $report->setManufacturer($this->manufacturer);

        $this->reportRepository->persist($report);

        try {
            if ($options['valid_bar_codes']) {
                $this->validateBarCodes();
            }

            if ($options['valid_vendor_numbers']) {
                $this->validateVendorNumbers();
            }

            $qb = $this->queryBuilder();

            /** @var StockMovementInterface[] $stockMovements */
            $stockMovements = $qb->getQuery()->getResult();

            if (!count($stockMovements)) {
                throw new \Exception('No stock movements applicable for this report');
            }

            $lastStockMovement = null;
            foreach ($stockMovements as $stockMovement) {
                $report->addStockMovement($stockMovement);

                $lastStockMovement = $stockMovement;
            }

            if ($lastStockMovement && $options['update_last_stock_movement']) {
                $this->manufacturer->setConsignmentLastStockMovement($lastStockMovement);
            }

            $report->markAsSuccess();
        } catch (\Exception $e) {
            $report->markAsError($e->getMessage());
        }

        $this->reportRepository->flush();

        return $report;
    }

    /**
     * @param ReportInterface $report
     * @param array           $options
     *
     * @return \SplFileObject
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function generateReportFile(ReportInterface $report, array $options = []): \SplFileObject
    {
        $file = $this->getFile();

        foreach ($report->getStockMovements() as $stockMovement) {
            $file->fputcsv([
                $stockMovement->getQuantity(),
                $stockMovement->getProduct()->getBarCodeNumber(),
            ]);
        }

        $report->setFile($file);

        $this->reportRepository->flush();

        return $file;
    }

    /**
     * @param ReportInterface $report
     * @param array           $options
     *
     * @return bool
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deliverReport(ReportInterface $report, array $options = []): bool
    {
        if (!$report->isDeliverable()) {
            return false;
        }

        $file = $report->getFile();
        if (!$file || !$file->isFile()) {
            $file = $this->generateReportFile($report);
        }

        $this->logger->info('File has been delivered to: '.$file->getPathname());

        /*
        Example of mailing the report
        -------
        $recipients = ['johndoe@example.com'];

        $attachment = \Swift_Attachment::fromPath($file->getPathname());
        $attachment->setFilename('report-'.$report->getId().'.csv');

        $message = \Swift_Message::newInstance()
            ->attach($attachment)
            ->setSubject('Consignment report (id: '.$report->getId().')')
            ->setFrom('william@yourbusiness.com', 'Your Business')
            ->setTo($recipients)
            ->setBody('See the attached file.', 'text/plain')
        ;

        $this->mailer->send($message);
        */

        return true;
    }

    public function queryBuilder(array $options = [], string $alias = 's'): QueryBuilder
    {
        // resolve options
        $resolver = new OptionsResolver();
        $this->configureQueryBuilderOptions($resolver);
        $options = $resolver->resolve($options);

        $includedProductIds = $this->getIncludedProductIds();

        if (!count($includedProductIds)) {
            throw new \RuntimeException('No included product ids. Something is wrong');
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('s, p')
            ->from('Loevgaard\DandomainStock\Entity\StockMovement', 's')
            ->join('s.product', 'p')
            ->andWhere($qb->expr()->in('p.id', ':includedProductIds'))
            ->andWhere($qb->expr()->in('s.type', ':stockMovementTypes'))
            ->addOrderBy('s.id', 'asc')
            ->setParameters([
                'includedProductIds' => $includedProductIds,
                'stockMovementTypes' => $options['stock_movement_types'],
            ]);

        if (!$options['include_complaints']) {
            $qb->andWhere('s.complaint = 0');
        }

        if ($options['use_last_stock_movement'] && $this->manufacturer->getConsignmentLastStockMovement()) {
            $qb->andWhere($qb->expr()->gt('s.id', ':lastStockMovementId'))
                ->setParameter('lastStockMovementId', $this->manufacturer->getConsignmentLastStockMovement()->getId());
        }

        if ($options['start_date']) {
            $qb->andWhere($qb->expr()->gte('s.createdAt', ':startDate'))
                ->setParameter('startDate', $options['start_date']);
        }

        if ($options['end_date']) {
            $qb->andWhere($qb->expr()->lte('s.createdAt', ':endDate'))
                ->setParameter('endDate', $options['end_date']);
        }

        return $qb;
    }

    public function getProductQueryBuilder(string $alias = 'p'): QueryBuilder
    {
        $excludedProductIds = $this->getExcludedProductIds();

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select($alias)
            ->from('Loevgaard\DandomainFoundation\Entity\Product', $alias)
            ->where($qb->expr()->isMemberOf(':manufacturer', $alias.'.manufacturers'))
            ->setParameter('manufacturer', $this->manufacturer);

        if (!empty($excludedProductIds)) {
            $qb->andWhere($qb->expr()->notIn($alias.'.id', ':excluded'))
                ->setParameter('excluded', $excludedProductIds);
        }

        return $qb;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return ConsignmentServiceInterface
     */
    public function setLogger(LoggerInterface $logger): ConsignmentServiceInterface
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param ManufacturerInterface $manufacturer
     *
     * @return ConsignmentServiceInterface
     */
    public function setManufacturer(ManufacturerInterface $manufacturer): ConsignmentServiceInterface
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    /**
     * This method should return an array of included product ids
     * It excludes the excluded product ids, by using the getProductQueryBuilder method.
     *
     * @return array
     */
    protected function getIncludedProductIds(): array
    {
        if (!$this->includedProductIds) {
            $qb = $this->getProductQueryBuilder();
            $qb->select('p.id');

            $res = $qb->getQuery()->getArrayResult();

            $this->includedProductIds = array_map(function ($elm) {
                return array_values($elm)[0];
            }, $res);
        }

        return $this->includedProductIds;
    }

    /**
     * This method should return an array of excluded product ids.
     *
     * @return array
     */
    protected function getExcludedProductIds(): array
    {
        return [];
    }

    /**
     * This is a helper method which takes an array of product numbers and returns their respective product ids.
     *
     * @param array $numbers
     *
     * @return array
     */
    protected function getProductIdsFromProductNumbers(array $numbers): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('p.id')
            ->from('Loevgaard\DandomainFoundation\Entity\Product', 'p')
            ->where($qb->expr()->in('p.number', ':numbers'))
            ->setParameter('numbers', $numbers);

        $res = $qb->getQuery()->getArrayResult();

        return array_map(function ($elm) {
            return array_values($elm)[0];
        }, $res);
    }

    /**
     * @throws InvalidBarCodeException
     */
    protected function validateBarCodes(): void
    {
        $qb = $this->queryBuilder();
        $qb->andWhere('p.validBarCode = 0');

        /** @var StockMovementInterface[] $stockMovements */
        $stockMovements = $qb->getQuery()->getResult();

        $c = count($stockMovements);

        if ($c) {
            $this->logger->emergency('There are '.$c.' stock movements with invalid bar codes');
            $productNumbers = [];
            foreach ($stockMovements as $stockMovement) {
                $productNumbers[] = $stockMovement->getProduct()->getNumber();
            }

            throw new InvalidBarCodeException('Products with invalid bar codes: '.join(', ', $productNumbers), $productNumbers);
        }
    }

    /**
     * @throws InvalidVendorNumberException
     */
    protected function validateVendorNumbers(): void
    {
        $qb = $this->queryBuilder();
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->eq('p.vendorNumber', ':empty'),
            $qb->expr()->isNull('p.vendorNumber')
        ))->setParameter(':empty', '');

        /** @var StockMovementInterface[] $stockMovements */
        $stockMovements = $qb->getQuery()->getResult();

        $c = count($stockMovements);

        if ($c) {
            $this->logger->critical('There are '.$c.' stock movements with invalid vendor numbers');
            $productNumbers = [];
            foreach ($stockMovements as $stockMovement) {
                $productNumbers[] = $stockMovement->getProduct()->getNumber();
            }

            throw new InvalidVendorNumberException('Products with invalid vendor numbers: '.join($productNumbers), $productNumbers);
        }
    }

    protected function getFile(string $extension = 'csv'): \SplFileObject
    {
        do {
            $filename = $this->reportDir.'/'.uniqid('consignment-', true).'.'.$extension;
        } while (file_exists($filename));

        return new \SplFileObject($filename, 'w+');
    }

    protected function configureGenerateReportOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined($this->queryBuilderOptions());
        $resolver->setDefaults([
            'valid_bar_codes' => false,
            'valid_vendor_numbers' => false,
            'update_last_stock_movement' => true,
        ]);
    }

    protected function configureQueryBuilderOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined($this->queryBuilderOptions());
        $resolver->setDefaults([
            'stock_movement_types' => [
                StockMovement::TYPE_RETURN,
                StockMovement::TYPE_SALE,
                StockMovement::TYPE_REGULATION,
            ],
            'include_complaints' => false,
            'use_last_stock_movement' => true,
            'start_date' => null,
            'end_date' => null,
        ]);
    }

    protected function queryBuilderOptions(): array
    {
        return [
            'stock_movement_types',
            'include_complaints',
            'use_last_stock_movement',
            'start_date',
            'end_date',
        ];
    }
}
