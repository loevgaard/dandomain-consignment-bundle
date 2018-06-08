<?php

declare(strict_types=1);

namespace Loevgaard\DandomainConsignmentBundle\ConsignmentService;

use Loevgaard\DandomainConsignmentBundle\Exception\NonExistentConsignmentServiceException;
use Loevgaard\DandomainFoundation\Entity\Generated\ManufacturerInterface;
use Symfony\Component\DependencyInjection\Container;

class ConsignmentServiceCollection
{
    /**
     * @var ConsignmentServiceInterface[]
     */
    protected $consignmentServices;

    public function __construct()
    {
        $this->consignmentServices = [];
    }

    public function addConsignmentService(ConsignmentServiceInterface $consignmentService): void
    {
        $class = (new \ReflectionClass($consignmentService))->getShortName();
        $this->consignmentServices[$class] = $consignmentService;
    }

    /**
     * @param ManufacturerInterface $manufacturer
     *
     * @return ConsignmentServiceInterface
     *
     * @throws NonExistentConsignmentServiceException
     */
    public function findConsignmentService(ManufacturerInterface $manufacturer): ConsignmentServiceInterface
    {
        $name = preg_replace('/[^a-zA-Z0-9 ]+/i', ' ', $manufacturer->getName());
        $name = Container::camelize($name).'ConsignmentService';

        if (!isset($this->consignmentServices[$name])) {
            throw new NonExistentConsignmentServiceException('Consignment service `'.$name.'` not found. Did you mean any of these? '.join(', ', array_keys($this->consignmentServices)));
        }

        /** @var ConsignmentServiceInterface $service */
        $service = $this->consignmentServices[$name];
        $service->setManufacturer($manufacturer);

        return $service;
    }
}
