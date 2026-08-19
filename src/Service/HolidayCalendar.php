<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeInterface;

/**
 * Сервис для работы с праздничными днями (по ТК РФ, без учёта переносов).
 */
class HolidayCalendar
{
    /**
     * Список праздничных дней (месяц => дни).
     * Новогодние каникулы: 1, 7 января, 23 февраля, 8 марта, 1 мая, 9 мая, 12 июня, 4 ноября.
     *
     * @var int[][]
     */
    private array $holidays = [
        1  => [
            1,
            7,
        ],
        2  => [23],
        3  => [8],
        5  => [
            1,
            9,
        ],
        6  => [12],
        11 => [4],
    ];

    /**
     * Проверяет, является ли дата праздничным днём.
     */
    public function isHoliday(DateTimeInterface $date): bool
    {
        $month = (int) $date->format('n');
        $day = (int) $date->format('j');

        return isset($this->holidays[$month]) && in_array($day, $this->holidays[$month], true);
    }// end isHoliday()

    /**
     * Возвращает массив дат праздников, попадающих в диапазон [start; end].
     *
     * @return DateTimeInterface[]
     */
    public function getHolidaysBetween(DateTimeInterface $start, DateTimeInterface $end): array
    {
        $holidays = [];
        $current = clone $start;
        $current->setTime(0, 0);

        while ($current <= $end) {
            if ($this->isHoliday($current)) {
                $holidays[] = clone $current;
            }
            $current->modify('+1 day');
        }

        return $holidays;
    }// end getHolidaysBetween()

    /**
     * Возвращает количество праздничных дней в диапазоне.
     */
    public function countHolidaysBetween(DateTimeInterface $start, DateTimeInterface $end): int
    {
        return count($this->getHolidaysBetween($start, $end));
    }// end countHolidaysBetween()
}// end class
