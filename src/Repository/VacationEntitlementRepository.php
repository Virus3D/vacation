<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\VacationEntitlement;
use DateTimeInterface;
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
    public function getDaysForEmployeeOnDate(int $employeeId, DateTimeInterface $date): int
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

    /**
     * Загрузить фиксированные дни для нескольких сотрудников и дат за один запрос.
     *
     * @param array<array{employeeId: int, date: string}> $dateRequests
     * @return array<string, int>  ключ "employeeId|date" => days
     */
    public function getDaysForEmployeesOnDatesBatch(array $dateRequests): array
    {
        if ($dateRequests === []) {
            return [];
        }

        // Собираем уникальные employeeId.
        $employeeIds = array_unique(array_column($dateRequests, 'employeeId'));

        $qb = $this->createQueryBuilder('ve')
            ->join('ve.employee', 'e')
            ->select('e.id', 've.startDate', 've.endDate', 've.days')
            ->andWhere('e.id IN (:employeeIds)')
            ->setParameter('employeeIds', $employeeIds)
            ->orderBy('e.id', 'ASC')
            ->addOrderBy('ve.startDate', 'ASC');

        $rows = $qb->getQuery()->getResult();

        // Группируем строки по employeeId.
        $byEmployee = [];
        foreach ($rows as $row) {
            $byEmployee[$row['id']][] = [
                'startDate' => $row['startDate'],
                'endDate'   => $row['endDate'],
                'days'      => (int) $row['days'],
            ];
        }

        $result = [];
        foreach ($dateRequests as $req) {
            $date = $req['yearStart']->format('Y-m-d');
            $key = $req['employeeId'] . '|' . $date;
            $employeeRows = $byEmployee[$req['employeeId']] ?? [];

            $total = 0;
            foreach ($employeeRows as $ent) {
                $entStart = $ent['startDate'] instanceof DateTimeInterface
                    ? $ent['startDate']->format('Y-m-d')
                    : $ent['startDate'];
                $entEnd = $ent['endDate'] instanceof DateTimeInterface
                    ? $ent['endDate']->format('Y-m-d')
                    : $ent['endDate'];

                if ($entStart <= $date && ($entEnd === null || $entEnd >= $date)) {
                    $total += $ent['days'];
                }
            }
            $result[$key] = $total;
        }

        return $result;
    }// end getDaysForEmployeesOnDatesBatch()
}// end class
