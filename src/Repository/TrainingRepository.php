<?php

namespace App\Repository;

use App\Entity\Training;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Training>
 */
class TrainingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Training::class);
    }

    /**
     * Récupère les formations actives d'une entreprise
     * @return Training[]
     */
    public function findActiveByCompany(int $companyId): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.employee', 'e')
            ->innerJoin('e.company', 'c')
            ->where('c.id = :companyId')
            ->andWhere('t.status = :status')
            ->setParameter('companyId', $companyId)
            ->setParameter('status', Training::STATUS_ACTIVE)
            ->getQuery()
            ->getResult();
    }
}
