<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Employee;
use App\Entity\Vacation;
use App\Entity\VacationDetail;
use App\Repository\VacationDetailRepository;
use App\Repository\VacationEntitlementRepository;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;

class VacationManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private VacationDetailRepository $vacationDetailRepository,
        private VacationEntitlementRepository $vacationEntitlementRepository,
        private HolidayCalendar $holidayCalendar
    ) {
    }// end __construct()

    /**
     * Получить рабочие годы сотрудника
     *
     * @return array<array<mixed>>
     */
    public function getWorkYears(Employee $employee): array
    {
        $hireDate = $employee->getHireDate();
        $today = new \DateTime();
        $workYears = [];

        $yearStart = clone $hireDate;
        $yearCounter = 1;

        while ($yearStart <= $today) {
            $yearEnd = clone $yearStart;
            $yearEnd->modify('+1 year');
            $yearEnd->modify('-1 day');

            // Рассчитываем дополнительные дни (1 день за каждый год, максимум 10).
            $seniorityAdditionalDays = min(
                $employee->getAdditionalVacationDays() + $yearCounter - 1,
                $employee->getMaxSeniorityAdditionalDays() ?? 10
            );

            // Фиксированные дополнительные дни, действующие на начало рабочего года.
            $fixedAdditionalDays = $this->vacationEntitlementRepository->getDaysForEmployeeOnDate(
                $employee->getId(),
                $yearStart
            );

            // Если год ещё не завершён (текущий рабочий год).
            if ($yearEnd >= $today && $yearStart <= $today) {
                // Количество полных отработанных месяцев.
                $interval = $yearStart->diff($today);
                $monthsWorked = ($interval->y * 12) + $interval->m;

                // Пропорциональный расчёт дней.
                $mainDays = (int) floor($employee->getBaseVacationDays() * $monthsWorked / 12);
                $seniorityDays = (int) floor($seniorityAdditionalDays * $monthsWorked / 12);
                $fixedDays = (int) floor($fixedAdditionalDays * $monthsWorked / 12);
            } else {
                // Для завершённых (прошлых) лет – полные дни.
                $mainDays = $employee->getBaseVacationDays();
                $seniorityDays = $seniorityAdditionalDays;
                $fixedDays = $fixedAdditionalDays;
            }

            $workYears[] = [
                'year_number'     => $yearCounter,
                'start_date'      => clone $yearStart,
                'end_date'        => clone $yearEnd,
                'main_days'       => $mainDays,
                'seniority_days'  => $seniorityDays,
                'fixed_days'      => $fixedDays,
                'additional_days' => $seniorityDays + $fixedDays,
                'total_days'      => $employee->getBaseVacationDays() + $seniorityDays + $fixedDays,
            ];

            $yearStart->modify('+1 year');
            $yearCounter++;
        }// end while

        return $workYears;
    }// end getWorkYears()

    /**
     * Получить остаток дней на текущий момент.
     *
     * @return array<string, mixed>
     */
    public function getRemainingDays(Employee $employee): array
    {
        $workYears = $this->getWorkYears($employee);
        $today = new \DateTime();

        $remaining = [
            'main'       => 0,
            'additional' => 0,
            'total'      => 0,
            'details'    => [],
        ];

        foreach ($workYears as $year) {
            $usedDays = $this->vacationDetailRepository->getUsedDaysByYear(
                $employee->getId(),
                $year['start_date'],
                $year['end_date']
            );
            $mainRemaining = $year['main_days'] - $usedDays['main'];
            $additionalRemaining = $year['additional_days'] - $usedDays['additional'];

            $remaining['details'][] = [
                'year_number'          => $year['year_number'],
                'period'               => $year['start_date']->format('d.m.Y') . ' - ' . $year['end_date']->format('d.m.Y'),
                'main_remaining'       => $mainRemaining,
                'additional_remaining' => $additionalRemaining,
                'total_remaining'      => $mainRemaining + $additionalRemaining,
            ];

            // Учитываем только текущий и прошлые годы.
            if ($today >= $year['start_date']) {
                $remaining['main'] += max(0, $mainRemaining);
                $remaining['additional'] += max(0, $additionalRemaining);
            }
        }// end foreach

        $remaining['total'] = $remaining['main'] + $remaining['additional'];
        return $remaining;
    }// end getRemainingDays()

    /**
     * Рассчитать использование отпускных дней
     *
     * @return array<string, mixed>
     */
    public function calculateVacationUsage(
        Employee $employee,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate
    ): array {
        $duration = $this->getVacationDaysCount($startDate, $endDate);
        $workYears = $this->getWorkYears($employee);
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
    public function addVacation(Employee $employee, DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        $calculation = $this->calculateVacationUsage($employee, $startDate, $endDate);

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
        array $manualDetails
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

            $available = $this->getAvailableDaysForYear($employee, $yearStart, $yearEnd, $type);

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
        string $type
    ): int {
        $workYears = $this->getWorkYears($employee);
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
}// end class
