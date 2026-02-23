<?php

namespace App\Repository;

use App\Entity\Employee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Employee>
 */
class EmployeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employee::class);
    }

    /**
     * Récupère les employés disponibles d'une entreprise
     * @return Employee[]
     */
    public function findAvailableByCompany(int $companyId): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.company', 'c')
            ->where('c.id = :companyId')
            ->andWhere('e.availabilityStatus = :status')
            ->setParameter('companyId', $companyId)
            ->setParameter('status', Employee::STATUS_DISPO)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les employés actifs (non en arrêt) d'une entreprise
     * @return Employee[]
     */
    public function findActiveByCompany(int $companyId): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.company', 'c')
            ->where('c.id = :companyId')
            ->andWhere('e.availabilityStatus != :status')
            ->setParameter('companyId', $companyId)
            ->setParameter('status', Employee::STATUS_ARRET)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les employés d'une entreprise
     * @return Employee[]
     */
    public function findByCompany(int $companyId): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.company', 'c')
            ->where('c.id = :companyId')
            ->setParameter('companyId', $companyId)
            ->getQuery()
            ->getResult();
    }
}
