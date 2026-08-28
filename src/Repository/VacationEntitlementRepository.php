<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\VacationEntitlement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VacationEntitlementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VacationEntitlement::class);
    }// end __construct()

    /**
     * Получить количество фиксированных дополнительных дней для сотрудника на дату.
     */
    public function getDaysForEmployeeOnDate(int $employeeId, \DateTimeInterface $date): int
    {
        $qb = $this->createQueryBuilder('ve')
            ->select('COALESCE(SUM(ve.days), 0) as totalDays')
            ->andWhere('ve.employee = :employeeId')
            ->andWhere('ve.startDate <= :date')
            ->andWhere('ve.endDate IS NULL OR ve.endDate >= :date')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('date', $date->format('Y-m-d'));

        $result = $qb->getQuery()->getSingleScalarResult();

        return (int) $result;
    }// end getDaysForEmployeeOnDate()
}// end class
