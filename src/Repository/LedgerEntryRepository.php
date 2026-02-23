<?php

namespace App\Repository;

use App\Entity\LedgerEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LedgerEntry>
 */
class LedgerEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LedgerEntry::class);
    }

    /**
     * Récupère les entrées du journal pour une entreprise
     * @return LedgerEntry[]
     */
    public function findByCompany(int $companyId, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('le')
            ->innerJoin('le.company', 'c')
            ->where('c.id = :companyId')
            ->setParameter('companyId', $companyId)
            ->orderBy('le.simDay', 'DESC')
            ->addOrderBy('le.createdAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }
}
