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
        $result = $this->createQueryBuilder('ve')
            ->andWhere('ve.employee = :employeeId')
            ->andWhere('ve.startDate <= :date')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('ve.startDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result ? $result->getDays() : 0;
    }// end getDaysForEmployeeOnDate()
}// end class
