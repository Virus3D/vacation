<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Employee;
use App\Entity\NonAccrualPeriod;
use App\Entity\Vacation;
use App\Entity\VacationDetail;
use App\Repository\NonAccrualPeriodRepository;
use App\Repository\VacationDetailRepository;
use App\Repository\VacationEntitlementRepository;
use App\Repository\VacationPlanRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;

class VacationManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private VacationDetailRepository $vacationDetailRepository,
        private VacationEntitlementRepository $vacationEntitlementRepository,
        private VacationPlanRepository $vacationPlanRepository,
        private NonAccrualPeriodRepository $nonAccrualPeriodRepository,
        private HolidayCalendar $holidayCalendar
    ) {
    }// end __construct()

    /**
     * Получить рабочие годы.
     *
     * @return array<array<mixed>>
     */
    public function getWorkYears(Employee $employee, ?DateTimeInterface $current = null, bool $allowAdvance = false): array
    {
        if ($current === null) {
            $current = new DateTime();
        }

        $allNonAccrualPeriods = $this->nonAccrualPeriodRepository->findByEmployees([$employee->getId()]);
        $excludedPeriods = $allNonAccrualPeriods[$employee->getId()] ?? [];

        $yearsList = $this->getWorkYearList($employee, $current);

        $usedDaysMap = $this->vacationDetailRepository->getUsedDaysByYearBatch($yearsList);

        $yearCounter = 1;
        foreach ($yearsList as $usedPeriod) {
            $yearStart = $usedPeriod['yearStart'];
            $yearEnd = $usedPeriod['yearEnd'];

            $entKey = $employee->getId() . '|' . $yearStart->format('Y-m-d');
            $fixedAdditionalDays = $entitlementsMap[$entKey] ?? 0;
            $days = $this->getWorkYearDays(
                $employee,
                $yearCounter,
                $usedPeriod['cutoffDate'],
                $yearStart,
                $yearEnd,
                $excludedPeriods,
                $fixedAdditionalDays,
                $allowAdvance
            );

            // Используемые дни.
            $usedKey = $employee->getId() . '|' . $yearStart->format('Y-m-d') . '|' . $yearEnd->format('Y-m-d');
            $usedDays = $usedDaysMap[$usedKey] ?? [
                'main'       => 0,
                'additional' => 0,
            ];

            $days['used'] = $usedDays;
            $days['remaining'] = [
                'main'       => $days['main_days'] - $usedDays['main'],
                'additional' => $days['additional_days'] - $usedDays['additional'],
            ];
            $days['used']['total'] = $days['used']['main'] + $days['used']['additional'];

            $days['remaining']['total'] = $days['remaining']['main'] + $days['remaining']['additional'];

            $workYears[] = $days;
            $yearCounter++;
        }// end foreach

        return $workYears;
    }// end getWorkYears()

    /**
     * Получить рабочие годы сотрудника (вынесенная логика).
     *
     * @return array<array<mixed>>
     */
    private function getWorkYearList(
        Employee $employee,
        DateTimeInterface $currentDate,
    ): array {
        $id = $employee->getId();

        $hireDate = $employee->getHireDate();

        $cutoffDate = $employee->isTerminated()
            ? ($employee->getTerminationDate() ?? $currentDate)
            : $currentDate;

        $limitDate = $employee->isTerminated() ? $cutoffDate : (clone $cutoffDate)->modify('+6 months');

        $workYears = [];

        $yearStart = clone $hireDate;
        $yearCounter = 1;

        while ($yearStart <= $limitDate) {
            // Границы года: +1 год −1 день.
            $yearEnd = (clone $yearStart)->modify('+1 year')->modify('-1 day');

            $workYears[] = [
                'employeeId' => $id,
                'yearStart'  => $yearStart,
                'yearEnd'    => $yearEnd,
                'cutoffDate' => $cutoffDate,
            ];

            $yearStart = (clone $yearEnd)->modify('+1 day');
            $yearCounter++;
        }// end while

        return $workYears;
    }// end getWorkYearList()

    /**
     * @param NonAccrualPeriod[] $excludedPeriods
     *
     * @return array<string, mixed>
     */
    private function getWorkYearDays(
        Employee $employee,
        int $yearCounter,
        DateTimeInterface $cutoffDate,
        DateTimeInterface $yearStart,
        DateTimeInterface $yearEnd,
        array $excludedPeriods,
        int $fixedAdditionalDays,
        bool $allowAdvance,
    ): array {
        // Всего календарных дней в году.
        $totalDaysInYear = $yearStart->diff($yearEnd)->days + 1;

        // Дни без начисления, попавшие в этот рабочий год.
        $excludedInYear = $this->countExcludedDaysInRange($yearStart, $yearEnd, $excludedPeriods);

        // Отработано в году (без исключений).
        $workedDaysFullYear = max(0, $totalDaysInYear - $excludedInYear);

        // Рассчитываем дополнительные дни (1 день за каждый год, максимум 10).
        $seniorityAdditionalDays = min(
            $employee->getAdditionalVacationDays() + $yearCounter - 1,
            $employee->getMaxSeniorityAdditionalDays() ?? 10
        );

        // Если год ещё не завершён (текущий рабочий год).
        if ($yearEnd >= $cutoffDate && $yearStart <= $cutoffDate && !$allowAdvance) {
            // Отработано до сегодня (без исключений).
            $workedDaysToDate = $cutoffDate->diff($yearStart)->days + 1;
            $excludedToDate   = $this->countExcludedDaysInRange($yearStart, $cutoffDate, $excludedPeriods);
            $workedToDate     = max(0, $workedDaysToDate - $excludedToDate);

            $ratio = $totalDaysInYear > 0 ? min(1, $workedToDate / $totalDaysInYear) : 0;

            // Пропорциональный расчёт дней.
            $mainDays = (int) ceil($employee->getBaseVacationDays() * $ratio);
            $seniorityDays = (int) ceil($seniorityAdditionalDays * $ratio);
            $fixedDays = (int) ceil($fixedAdditionalDays * $ratio);
        } else if ($yearStart > $cutoffDate && !$allowAdvance) {
            $mainDays = 0;
            $seniorityDays = 0;
            $fixedDays = 0;
        } else {
            // Для завершённых (прошлых) лет – полные дни.
            $ratio = $totalDaysInYear > 0 ? $workedDaysFullYear / $totalDaysInYear : 0;
            $mainDays = (int) ceil($employee->getBaseVacationDays() * $ratio);
            $seniorityDays = (int) ceil($seniorityAdditionalDays * $ratio);
            $fixedDays = (int) ceil($fixedAdditionalDays * $ratio);
        }// end if

        return [
            'year_number'     => $yearCounter,
            'cutoffDate'      => $cutoffDate,
            'start_date'      => clone $yearStart,
            'end_date'        => clone $yearEnd,
            'main_days'       => $mainDays,
            'seniority_days'  => $seniorityDays,
            'fixed_days'      => $fixedDays,
            'additional_days' => $seniorityDays + $fixedDays,
            'total_days'      => $mainDays + $seniorityDays + $fixedDays,
        ];
    }// end getWorkYearDays()

    /**
     * Количество дней пересечения двух диапазонов (включительно).
     */
    private function countOverlapDays(
        DateTimeInterface $aStart,
        DateTimeInterface $aEnd,
        DateTimeInterface $bStart,
        DateTimeInterface $bEnd
    ): int {
        $start = max($aStart, $bStart);
        $end = min($aEnd, $bEnd);

        if ($start > $end) {
            return 0;
        }

        return $start->diff($end)->days + 1;
    }// end countOverlapDays()

    /**
     * Количество исключённых дней в диапазоне [from, to].
     *
     * @param array<int, NonAccrualPeriod> $excludedPeriods
     */
    private function countExcludedDaysInRange(
        DateTimeInterface $from,
        DateTimeInterface $to,
        array $excludedPeriods
    ): int {
        $days = 0;
        foreach ($excludedPeriods as $period) {
            $days += $this->countOverlapDays($from, $to, $period->getStartDate(), $period->getEndDate());
        }
        return $days;
    }// end countExcludedDaysInRange()

    /**
     * Пакетно получить остатки дней для нескольких сотрудников за 3 запроса к БД.
     *
     * @param Employee[] $employees
     *
     * @return array<int, array<string, mixed>>  ключ => employeeId, значение => ['main', 'additional', 'total']
     */
    public function getRemainingDaysBatch(array $employees, ?DateTimeInterface $currentDate): array
    {
        if ($employees === []) {
            return [];
        }

        if ($currentDate === null) {
            $currentDate = new Datetime();
        }

        $employeeIds = [];
        $employeeMap = [];
        foreach ($employees as $emp) {
            $id = $emp->getId();
            $employeeIds[] = $id;
            $employeeMap[$id] = $emp;
        }

        // 1. Загружаем все non-accrual periods одним запросом.
        $allNonAccrualPeriods = $this->nonAccrualPeriodRepository->findByEmployees($employeeIds);

        // 2. Для каждого сотрудника вычисляем рабочие годы.
        // массив массивов {employeeId, yearStart, yearEnd}
        $usedPeriodsAll = [];
        foreach ($employeeIds as $id) {
            $usedPeriodsAll = array_merge($usedPeriodsAll, $this->getWorkYearList($employeeMap[$id], $currentDate));
        }

        // 3. Загружаем fixed additional days для всех (employeeId, yearStart) пар одним запросом.
        $entitlementsMap = $this->vacationEntitlementRepository->getDaysForEmployeesOnDatesBatch($usedPeriodsAll);

        // 4. Загружаем использованные дни для всех (employeeId, yearStart, yearEnd) пар одним запросом.
        $usedDaysMap = $this->vacationDetailRepository->getUsedDaysByYearBatch($usedPeriodsAll);

        // 5. Вычисляем остатки для каждого сотрудника.
        $result = [];

        foreach ($employeeIds as $id) {
            $employee = $employeeMap[$id];
            $usedPeriods = array_filter($usedPeriodsAll, fn($row) => $row['employeeId'] === $id);

            $excludedPeriods = $allNonAccrualPeriods[$id] ?? [];

            $mainTotal = 0;
            $additionalTotal = 0;

            $yearCounter = 1;
            foreach ($usedPeriods as $usedPeriod) {
                $yearStart = $usedPeriod['yearStart'];
                $yearEnd = $usedPeriod['yearEnd'];

                $entKey = $id . '|' . $yearStart->format('Y-m-d');
                $fixedAdditionalDays = $entitlementsMap[$entKey] ?? 0;
                $days = $this->getWorkYearDays(
                    $employee,
                    $yearCounter,
                    $usedPeriod['cutoffDate'],
                    $yearStart,
                    $yearEnd,
                    $excludedPeriods,
                    $fixedAdditionalDays,
                    false
                );

                // Используемые дни.
                $usedKey = $id . '|' . $yearStart->format('Y-m-d') . '|' . $yearEnd->format('Y-m-d');
                $usedDays = $usedDaysMap[$usedKey] ?? [
                    'main'       => 0,
                    'additional' => 0,
                ];

                $mainRemaining = $days['main_days'] - $usedDays['main'];
                $additionalRemaining = $days['additional_days'] - $usedDays['additional'];

                // Учитываем только годы, начавшиеся до cutoff.
                if ($usedPeriod['cutoffDate'] >= $yearStart) {
                    $mainTotal += $mainRemaining;
                    $additionalTotal += $additionalRemaining;
                }

                $yearCounter++;
            }// end foreach

            $result[$id] = [
                'main'       => $mainTotal,
                'additional' => $additionalTotal,
                'total'      => $mainTotal + $additionalTotal,
            ];
        }// end foreach

        return $result;
    }// end getRemainingDaysBatch()

    /**
     * Рассчитать использование отпускных дней
     *
     * @return array<string, mixed>
     */
    public function calculateVacationUsage(
        Employee $employee,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        bool $allowAdvance = false
    ): array {
        $duration = $this->getVacationDaysCount($startDate, $endDate);
        $workYears = $this->getWorkYears($employee, allowAdvance: $allowAdvance);
        $usage = [];
        $daysToUse = $duration;

        // Сортируем рабочие годы по возрастанию.
        usort(
            $workYears,
            function ($a, $b) {
                return $a['start_date'] <=> $b['start_date'];
            }
        );

        foreach ($workYears as $year) {
            if ($daysToUse <= 0) {
                break;
            }

            $usedDays = $this->vacationDetailRepository->getUsedDaysByYear(
                $employee->getId(),
                $year['start_date'],
                $year['end_date']
            );

            // Сначала используем основной отпуск.
            $mainAvailable = $year['main_days'] - $usedDays['main'];
            $mainToUse = min($mainAvailable, $daysToUse);

            if ($mainToUse > 0) {
                $usage[] = [
                    'work_year'  => $year['year_number'],
                    'period'     => $year['start_date']->format('d.m.Y') . ' - ' . $year['end_date']->format('d.m.Y'),
                    'start_date' => $year['start_date'],
                    'end_date'   => $year['end_date'],
                    'type'       => 'main',
                    'days'       => $mainToUse,
                ];
                $daysToUse -= $mainToUse;
            }

            // Затем используем дополнительный отпуск.
            if ($daysToUse > 0) {
                $additionalAvailable = $year['additional_days'] - $usedDays['additional'];
                $additionalToUse = min($additionalAvailable, $daysToUse);

                if ($additionalToUse > 0) {
                    $usage[] = [
                        'work_year'  => $year['year_number'],
                        'period'     => $year['start_date']->format('d.m.Y') . ' - ' . $year['end_date']->format('d.m.Y'),
                        'start_date' => $year['start_date'],
                        'end_date'   => $year['end_date'],
                        'type'       => 'additional',
                        'days'       => $additionalToUse,
                    ];
                    $daysToUse -= $additionalToUse;
                }
            }
        }// end foreach

        return [
            'total_days'        => $duration,
            'usage_details'     => $usage,
            'insufficient_days' => $daysToUse > 0 ? $daysToUse : 0,
        ];
    }// end calculateVacationUsage()

    /**
     * Добавить отпуск
     *
     * @return array<string, mixed>
     */
    public function addVacation(
        Employee $employee,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        bool $allowAdvance = false
    ): array {
        $calculation = $this->calculateVacationUsage($employee, $startDate, $endDate, $allowAdvance);

        if ($calculation['insufficient_days'] > 0) {
            return [
                'success' => false,
                'error'   => "Недостаточно отпускных дней. Не хватает: " . $calculation['insufficient_days'],
            ];
        }

        $this->entityManager->beginTransaction();

        try {
            $vacation = new Vacation();
            $vacation->setEmployee($employee);
            $vacation->setStartDate($startDate);
            $vacation->setEndDate($endDate);

            $this->entityManager->persist($vacation);

            foreach ($calculation['usage_details'] as $detail) {
                $vacationDetail = new VacationDetail();
                $vacationDetail->setVacation($vacation);
                $vacationDetail->setWorkYearStart($detail['start_date']);
                $vacationDetail->setWorkYearEnd($detail['end_date']);
                $vacationDetail->setDaysUsed($detail['days']);
                $vacationDetail->setVacationType($detail['type']);

                $this->entityManager->persist($vacationDetail);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            return ['success' => true];
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            return [
                'success' => false,
                'error'   => 'Ошибка при сохранении: ' . $e->getMessage(),
            ];
        }// end try
    }// end addVacation()

    /**
     * Возвращает количество календарных дней отпуска за вычетом праздничных дней.
     */
    public function getVacationDaysCount(DateTimeInterface $start, DateTimeInterface $end): int
    {
        $interval = $start->diff($end);
        // Включая начальный и конечный дни.
        $totalDays = $interval->days + 1;

        $holidaysCount = $this->holidayCalendar->countHolidaysBetween($start, $end);

        return $totalDays - $holidaysCount;
    }// end getVacationDaysCount()

    /**
     * Добавляет отпуск с ручной детализацией.
     *
     * @param array<array<string, mixed>> $manualDetails
     *
     * @return array{error: string, success: bool|array{success: bool}}
     */
    public function addVacationWithManualDetails(
        Employee $employee,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $manualDetails,
        bool $allowAdvance = false
    ): array {
        // Валидация и проверка остатков.
        $errors = [];
        // Временный учёт уже распределённых дней в этом запросе.
        $usedCombined = [];

        foreach ($manualDetails as $detail) {
            $yearStart = $detail['workYearStart'];
            $yearEnd = $detail['workYearEnd'];
            $type = $detail['vacationType'];
            $days = $detail['daysUsed'];

            if ($days <= 0) {
                $errors[] = 'Количество дней должно быть положительным.';
                continue;
            }

            $available = $this->getAvailableDaysForYear($employee, $yearStart, $yearEnd, $type, $allowAdvance);

            // Учитываем уже добавленные в этом запросе дни для того же года и типа.
            $key = $yearStart->format('Y-m-d') . '|' . $yearEnd->format('Y-m-d') . '|' . $type;
            $alreadyUsed = $usedCombined[$key] ?? 0;
            $remaining = $available - $alreadyUsed;

            if ($days > $remaining) {
                $errors[] = sprintf(
                    'Для периода %s - %s (%s) доступно только %d дней, а запрошено %d.',
                    $yearStart->format('d.m.Y'),
                    $yearEnd->format('d.m.Y'),
                    $type === 'main' ? 'основной' : 'дополнительный',
                    max(0, $remaining),
                    $days
                );
            } else {
                $usedCombined[$key] = $alreadyUsed + $days;
            }
        }// end foreach

        if (!empty($errors)) {
            return [
                'success' => false,
                'error'   => implode(' ', $errors),
            ];
        }

        // Сохранение.
        $this->entityManager->beginTransaction();
        try {
            $vacation = new Vacation();
            $vacation->setEmployee($employee);
            $vacation->setStartDate($startDate);
            $vacation->setEndDate($endDate);
            $this->entityManager->persist($vacation);

            foreach ($manualDetails as $detail) {
                $vacationDetail = new VacationDetail();
                $vacationDetail->setVacation($vacation);
                $vacationDetail->setWorkYearStart($detail['workYearStart']);
                $vacationDetail->setWorkYearEnd($detail['workYearEnd']);
                $vacationDetail->setDaysUsed($detail['daysUsed']);
                $vacationDetail->setVacationType($detail['vacationType']);
                $this->entityManager->persist($vacationDetail);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
            return ['success' => true];
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            return [
                'success' => false,
                'error'   => 'Ошибка сохранения: ' . $e->getMessage(),
            ];
        }// end try
    }// end addVacationWithManualDetails()

    private function getAvailableDaysForYear(
        Employee $employee,
        DateTimeInterface $yearStart,
        DateTimeInterface $yearEnd,
        string $type,
        bool $allowAdvance = false
    ): int {
        $workYears = $this->getWorkYears($employee, allowAdvance: $allowAdvance);
        foreach ($workYears as $year) {
            if ($year['start_date'] == $yearStart && $year['end_date'] == $yearEnd) {
                $usedDays = $this->vacationDetailRepository->getUsedDaysByYear(
                    $employee->getId(),
                    $yearStart,
                    $yearEnd
                );
                if ($type === 'main') {
                    return max(0, $year['main_days'] - $usedDays['main']);
                } else {
                    return max(0, $year['additional_days'] - $usedDays['additional']);
                }
            }
        }
        return 0;
    }// end getAvailableDaysForYear()

    /**
     * Рассчитать количество накопленных дней отпуска на указанную дату.
     *
     * @return array<string, int>
     */
    public function getAccruedDaysOnDate(Employee $employee, DateTimeInterface $date): array
    {
        $workYears = $this->getWorkYears($employee, $date);
        $remainingTotal = [
            'main'       => 0,
            'additional' => 0,
            'total'      => 0,
        ];
        foreach ($workYears as $index => $wy) {
            // Учитываем только годы, начавшиеся до cutoff.
            if ($wy['cutoffDate'] >= $wy['start_date']) {
                $remainingTotal['main'] += $wy['remaining']['main'];
                $remainingTotal['additional'] += $wy['remaining']['additional'];
            }
        }

        $remainingTotal['total'] = $remainingTotal['main'] + $remainingTotal['additional'];

        return $remainingTotal;
    }// end getAccruedDaysOnDate()

    /**
     * Рассчитать доступные дни для планирования отпуска на указанную дату (с учётом уже запланированных).
     *
     * @return array<string, int>
     */
    public function getAvailableDaysForPlanning(Employee $employee, DateTimeInterface $date): array
    {
        $accrued = $this->getAccruedDaysOnDate($employee, $date);

        // Вычитаем запланированные отпуска, начинающиеся до указанной даты.
        $plannedDays = [
            'main'       => 0,
            'additional' => 0,
        ];
        $plans = $this->vacationPlanRepository->findActivePlansBeforeDate($employee->getId(), $date);

        foreach ($plans as $plan) {
            // Упрощённо: считаем все дни плана как основные, но можно уточнить по деталям.
            $plannedDays['main'] += $plan->getTotalDays();
        }

        $available = [
            'main'       => max(0, $accrued['main'] - $plannedDays['main']),
            'additional' => max(0, $accrued['additional'] - $plannedDays['additional']),
        ];
        $available['total'] = $available['main'] + $available['additional'];

        return $available;
    }// end getAvailableDaysForPlanning()
}// end class
