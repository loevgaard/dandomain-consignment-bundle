<?php

declare(strict_types=1);

namespace Loevgaard\DandomainConsignmentBundle\Event;

use Loevgaard\DandomainConsignment\Entity\Generated\ReportInterface;
use Symfony\Component\EventDispatcher\Event;

class ReportNotDeliveredEvent extends Event
{
    const NAME = 'ldc.report.not_delivered';

    /**
     * @var ReportInterface
     */
    protected $report;

    public function __construct(ReportInterface $report)
    {
        $this->report = $report;
    }

    /**
     * @return ReportInterface
     */
    public function getReport(): ReportInterface
    {
        return $this->report;
    }
}
