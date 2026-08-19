<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Vacation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VacationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vacation::class);
    }// end __construct()

    public function findByEmployeeOrderedByDate(int $employeeId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.employee = :employeeId')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('v.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }// end findByEmployeeOrderedByDate()
}// end class
