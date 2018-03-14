<?php

declare(strict_types=1);

namespace Loevgaard\DandomainConsignmentBundle\Command;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Loevgaard\DandomainConsignmentBundle\Exception\NonExistentManufacturerException;
use Loevgaard\DandomainFoundation\Repository\ManufacturerRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnableCommand extends ContainerAwareCommand
{
    /**
     * @var ManufacturerRepository
     */
    protected $manufacturerRepository;

    public function __construct(ManufacturerRepository $manufacturerRepository)
    {
        $this->manufacturerRepository = $manufacturerRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('loevgaard:dandomain-consignment:enable')
            ->setDescription('Enables consignment for the specified manufacturer')
            ->addArgument('manufacturer', InputArgument::REQUIRED, 'The manufacturer')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     *
     * @throws NonExistentManufacturerException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // fetch arguments and options
        $manufacturer = $input->getArgument('manufacturer');

        // find manufacturer
        $manufacturer = $this->manufacturerRepository->findOneByExternalId($manufacturer);

        if (!$manufacturer) {
            throw new NonExistentManufacturerException('The manufacturer does not exist');
        }

        $manufacturer->setConsignment(true);
        $this->manufacturerRepository->flush();
    }
}
