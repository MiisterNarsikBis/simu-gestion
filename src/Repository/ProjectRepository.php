<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * Récupère les projets en cours d'une entreprise
     * @return Project[]
     */
    public function findInProgressByCompany(int $companyId): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.company', 'c')
            ->where('c.id = :companyId')
            ->andWhere('p.status IN (:statuses)')
            ->setParameter('companyId', $companyId)
            ->setParameter('statuses', [Project::STATUS_NEW, Project::STATUS_IN_PROGRESS])
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
