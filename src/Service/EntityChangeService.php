<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Company;
use App\Entity\Employee;

class EntityChangeService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function isEntityDirty(Company|Employee $entity): bool
    {
        $this->entityManager->persist($entity);
        $this->entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet($this->entityManager->getClassMetadata(get_class($entity)), $entity);
        $changeSet = $this->entityManager->getUnitOfWork()->getEntityChangeSet($entity);


        return !empty($changeSet);
    }
}
