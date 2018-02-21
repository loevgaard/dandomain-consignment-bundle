<?php

declare(strict_types=1);

namespace Loevgaard\DandomainConsignmentBundle\ConsignmentService;

use Doctrine\ORM\QueryBuilder;
use Loevgaard\DandomainConsignment\Entity\Generated\ReportInterface;
use Loevgaard\DandomainFoundation\Entity\Generated\ManufacturerInterface;
use Psr\Log\LoggerInterface;

interface ConsignmentServiceInterface
{
    /**
     * Returns a query builder to return the stock movements for this consignment
     *
     * @param array $options
     * @param string $alias
     * @return QueryBuilder
     */
    public function queryBuilder(array $options = [], string $alias = 's') : QueryBuilder;

    /**
     * Returns a query builder for the products to be included in the consignment report
     *
     * @param string $alias
     * @return QueryBuilder
     */
    public function getProductQueryBuilder(string $alias = 'p') : QueryBuilder;

    /**
     * This will generate a consignment report
     *
     * @param array $options
     * @return ReportInterface
     */
    public function generateReport(array $options = []) : ReportInterface;

    /**
     * This will generate the report file according to the consignors instructions
     *
     * @param ReportInterface $report
     * @param array $options
     * @return \SplFileObject
     */
    public function generateReportFile(ReportInterface $report, array $options = []) : \SplFileObject;

    /**
     * This will deliver a consignment report according to the agreement with the consignor
     *
     * @param ReportInterface $report
     * @param array $options
     * @return bool
     */
    public function deliverReport(ReportInterface $report, array $options = []) : bool;

    /**
     * Sets the manufacturer
     *
     * @param ManufacturerInterface $manufacturer
     * @return ConsignmentServiceInterface
     */
    public function setManufacturer(ManufacturerInterface $manufacturer) : ConsignmentServiceInterface;

    /**
     * Sets the logger
     *
     * @param LoggerInterface $logger
     * @return ConsignmentServiceInterface
     */
    public function setLogger(LoggerInterface $logger) : ConsignmentServiceInterface;
}
