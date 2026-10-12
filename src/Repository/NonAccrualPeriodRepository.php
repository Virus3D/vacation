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

    /**
     * Загрузить все периоды непредоставления отпуска для нескольких сотрудников за один запрос.
     *
     * @param int[] $employeeIds
     * @return array<int, NonAccrualPeriod[]>  ключ => employeeId, значение => список периодов
     */
    public function findByEmployees(array $employeeIds): array
    {
        if ($employeeIds === []) {
            return [];
        }

        $periods = $this->createQueryBuilder('p')
            ->andWhere('p.employee IN (:employeeIds)')
            ->setParameter('employeeIds', $employeeIds)
            ->orderBy('p.employee', 'ASC')
            ->addOrderBy('p.startDate', 'ASC')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($periods as $period) {
            $result[$period->getEmployee()->getId()][] = $period;
        }

        return $result;
    }// end findByEmployees()
}// end class
