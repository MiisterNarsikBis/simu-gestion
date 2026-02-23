<?php

namespace App\Repository;

use App\Entity\Loan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Loan>
 */
class LoanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Loan::class);
    }

    /**
     * Récupère les crédits actifs d'une entreprise
     * @return Loan[]
     */
    public function findActiveByCompany(int $companyId): array
    {
        return $this->createQueryBuilder('l')
            ->innerJoin('l.company', 'c')
            ->where('c.id = :companyId')
            ->andWhere('l.status = :status')
            ->setParameter('companyId', $companyId)
            ->setParameter('status', Loan::STATUS_ACTIVE)
            ->orderBy('l.startSimDay', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
