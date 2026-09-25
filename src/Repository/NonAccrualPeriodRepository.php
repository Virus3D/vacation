<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\NonAccrualPeriod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NonAccrualPeriodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NonAccrualPeriod::class);
    }// end __construct()

    /**
     * Находит все периоды непредоставления отпуска для сотрудника.
     *
     * @return NonAccrualPeriod[]
     */
    public function findByEmployee(int $employeeId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.employee = :id')
            ->setParameter('id', $employeeId)
            ->orderBy('p.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }// end findByEmployee()
}// end class
