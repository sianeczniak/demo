<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class EntityChangeService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function isEntityDirty($entity)
    {
        $this->entityManager->persist($entity);
        $this->entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet($this->entityManager->getClassMetadata(get_class($entity)), $entity);
        $changeSet = $this->entityManager->getUnitOfWork()->getEntityChangeSet($entity);


        return !empty($changeSet);
    }
}
