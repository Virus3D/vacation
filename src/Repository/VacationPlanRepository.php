<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\VacationPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VacationPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VacationPlan::class);
    }// end __construct()

    /**
     * Найти все планы по сотруднику, отсортированные по дате начала.
     *
     * @return VacationPlan[]
     */
    public function findByEmployee(int $employeeId): array
    {
        return $this->createQueryBuilder('vp')
            ->andWhere('vp.employee = :employeeId')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('vp.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }// end findByEmployee()

    /**
     * Найти планы, начинающиеся до указанной даты (включительно) и не отклонённые.
     *
     * @return VacationPlan[]
     */
    public function findActivePlansBeforeDate(int $employeeId, \DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('vp')
            ->andWhere('vp.employee = :employeeId')
            ->andWhere('vp.startDate <= :date')
            ->andWhere('vp.status != :rejected')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('rejected', 'rejected')
            ->orderBy('vp.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }// end findActivePlansBeforeDate()
}// end class
