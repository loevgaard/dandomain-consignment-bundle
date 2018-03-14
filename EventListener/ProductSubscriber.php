<?php

declare(strict_types=1);

namespace Loevgaard\DandomainConsignmentBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Loevgaard\DandomainFoundation\Entity\Generated\ProductInterface;
use violuke\Barcodes\BarcodeValidator;

class ProductSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        return $this->update($args);
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        return $this->update($args);
    }

    private function update(LifecycleEventArgs $args)
    {
        /** @var ProductInterface $entity */
        $entity = $args->getObject();

        if (!($entity instanceof ProductInterface)) {
            return false;
        }

        $entity->setValidBarCode(false);

        // if the bar code is set we validate the bar code
        if ($entity->getBarCodeNumber()) {
            $barcodeValidator = new BarcodeValidator($entity->getBarCodeNumber());

            if ($barcodeValidator->isValid()) {
                $entity->setValidBarCode(true);
            }
        }

        return true;
    }
}
