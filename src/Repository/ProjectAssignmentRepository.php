<?php

namespace App\Repository;

use App\Entity\ProjectAssignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectAssignment>
 */
class ProjectAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectAssignment::class);
    }

    /**
     * Récupère les assignations pour un projet et une étape
     * @return ProjectAssignment[]
     */
    public function findByProjectAndStage(int $projectId, string $stage): array
    {
        return $this->createQueryBuilder('pa')
            ->innerJoin('pa.project', 'p')
            ->where('p.id = :projectId')
            ->andWhere('pa.stage = :stage')
            ->setParameter('projectId', $projectId)
            ->setParameter('stage', $stage)
            ->getQuery()
            ->getResult();
    }
}
