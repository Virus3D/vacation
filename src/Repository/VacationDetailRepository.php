<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\VacationDetail;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VacationDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VacationDetail::class);
    }// end __construct()

    /**
     * Исползуемые дни в году.
     *
     * @return array<string, int>
     */
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

    /**
     * Загрузить использованные дни для нескольких сотрудников и периодов за один запрос.
     *
     * @param array<array{employeeId: int, yearStart: string, yearEnd: string}> $periods
     *
     * @return array<string, array<string, int>>  ключ "employeeId|yearStart|yearEnd" => ['main'=>N, 'additional'=>N]
     */
    public function getUsedDaysByYearBatch(array $periods): array
    {
        if ($periods === []) {
            return [];
        }

        // Собираем уникальные employeeId для оптимизации.
        $employeeIds = array_unique(array_column($periods, 'employeeId'));

        // Загружаем все детали для этих сотрудников.
        $qb = $this->createQueryBuilder('vd')
            ->join('vd.vacation', 'v')
            ->join('v.employee', 'e')
            ->select('e.id AS employeeId', 'vd.workYearStart', 'vd.workYearEnd', 'vd.vacationType', 'SUM(vd.daysUsed) AS daysUsed')
            ->andWhere('e.id IN (:employeeIds)')
            ->setParameter('employeeIds', $employeeIds)
            ->groupBy('e.id', 'vd.workYearStart', 'vd.workYearEnd', 'vd.vacationType');

        $rows = $qb->getQuery()->getResult();

        $result = [];
        foreach ($rows as $row) {
            $ws = $row['workYearStart'] instanceof DateTimeInterface
                ? $row['workYearStart']->format('Y-m-d')
                : $row['workYearStart'];
            $we = $row['workYearEnd'] instanceof DateTimeInterface
                ? $row['workYearEnd']->format('Y-m-d')
                : $row['workYearEnd'];

            $key = $row['employeeId'] . '|' . $ws . '|' . $we;
            if (!isset($result[$key])) {
                $result[$key] = [
                    'main'       => 0,
                    'additional' => 0,
                ];
            }
            $result[$key][$row['vacationType']] += (int) $row['daysUsed'];
        }

        return $result;
    }// end getUsedDaysByYearBatch()
}// end class
