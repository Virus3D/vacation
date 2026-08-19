<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\VacationDetail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VacationDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VacationDetail::class);
    }// end __construct()

    public function getUsedDaysByYear(int $employeeId, \DateTimeInterface $yearStart, \DateTimeInterface $yearEnd): array
    {
        $startStr = $yearStart->format('Y-m-d');
        $endStr = $yearEnd->format('Y-m-d');

        $qb = $this->createQueryBuilder('vd')
            ->join('vd.vacation', 'v')
            ->andWhere('v.employee = :employeeId')
            ->andWhere('vd.workYearStart = :yearStart')
            ->andWhere('vd.workYearEnd = :yearEnd')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('yearStart', $startStr)
            ->setParameter('yearEnd', $endStr);

        $results = $qb->getQuery()->getResult();

        $usedDays = [
            'main'       => 0,
            'additional' => 0,
        ];
        foreach ($results as $detail) {
            $usedDays[$detail->getVacationType()] += $detail->getDaysUsed();
        }

        return $usedDays;
    }// end getUsedDaysByYear()
}// end class
