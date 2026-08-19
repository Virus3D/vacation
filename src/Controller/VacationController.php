<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Employee;
use App\Form\VacationType;
use App\Service\VacationManager;
use App\Repository\EmployeeRepository;
use App\Repository\VacationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vacation')]
class VacationController extends AbstractController
{
    public function __construct(
        private VacationManager $vacationManager
    ) {
    }// end __construct()

    #[Route('/{id}', name: 'app_vacation_show', methods: ['GET', 'POST'])]
    public function show(
        Employee $employee,
        Request $request,
        VacationRepository $vacationRepository
    ): Response {
        $remainingDays = $this->vacationManager->getRemainingDays($employee);
        $vacations = $vacationRepository->findByEmployeeOrderedByDate($employee->getId());
        $vacationDaysExcludingHolidays = [];

        foreach ($vacations as $vacation) {
            $vacationDaysExcludingHolidays[$vacation->getId()] = $this->vacationManager
                ->getVacationDaysCount($vacation->getStartDate(), $vacation->getEndDate());
        }

        $workYears = $this->vacationManager->getWorkYears($employee);
        $workYearChoices = [];
        foreach ($workYears as $index => $wy) {
            $label = 'Год ' . $wy['year_number'] . ' (' . $wy['start_date']->format('d.m.Y') . ' - ' . $wy['end_date']->format('d.m.Y') . ')';
            $workYearChoices[$label] = $index;
        }

        $form = $this->createForm(
            VacationType::class,
            null,
            ['work_years_choices' => $workYearChoices]
        );
        $form->handleRequest($request);

        $calculationResult = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $startDate = $data['startDate'];
            $endDate = $data['endDate'];

            if ($form->get('calculate')->isClicked()) {
                $calculationResult = $this->vacationManager->calculateVacationUsage(
                    $employee,
                    $startDate,
                    $endDate
                );
            } elseif ($form->get('save')->isClicked()) {
                if ($data['auto_calculate']) {
                    $result = $this->vacationManager->addVacation($employee, $startDate, $endDate);
                    if ($result['success']) {
                        $this->addFlash('success', 'Отпуск успешно добавлен (автоматический расчёт).');
                        return $this->redirectToRoute('app_vacation_show', ['id' => $employee->getId()]);
                    } else {
                        $this->addFlash('error', $result['error']);
                    }
                } else {
                    // Ручное распределение
                    $manualDetails = [];
                    $detailsForm = $form->get('details');
                    foreach ($detailsForm as $detailForm) {
                        $workYearIndex = $detailForm->get('workYear')->getData();
                        $vacationType = $detailForm->get('vacationType')->getData();
                        $daysUsed = $detailForm->get('daysUsed')->getData();

                        if ($workYearIndex === null || $vacationType === null || $daysUsed === null) {
                            continue;
                        }

                        if (!isset($workYears[$workYearIndex])) {
                            $this->addFlash('error', 'Неверный выбор рабочего года.');
                            break;
                        }

                        $year = $workYears[$workYearIndex];
                        $manualDetails[] = [
                            'workYearStart' => $year['start_date'],
                            'workYearEnd'   => $year['end_date'],
                            'vacationType'  => $vacationType,
                            'daysUsed'      => $daysUsed,
                        ];
                    }// end foreach

                    if (empty($manualDetails)) {
                        $this->addFlash('error', 'Добавьте хотя бы одну строку распределения дней.');
                    } else {
                        $result = $this->vacationManager->addVacationWithManualDetails(
                            $employee,
                            $startDate,
                            $endDate,
                            $manualDetails
                        );
                        if ($result['success']) {
                            $this->addFlash('success', 'Отпуск успешно добавлен (ручное распределение).');
                            return $this->redirectToRoute('app_vacation_show', ['id' => $employee->getId()]);
                        } else {
                            $this->addFlash('error', $result['error']);
                        }
                    }
                }// end if
            }// end if
        }// end if

        return $this->render(
            'vacation/show.html.twig',
            [
                'employee'                      => $employee,
                'remainingDays'                 => $remainingDays,
                'vacations'                     => $vacations,
                'form'                          => $form->createView(),
                'calculationResult'             => $calculationResult,
                'vacationDaysExcludingHolidays' => $vacationDaysExcludingHolidays,
            ]
        );
    }// end show()
}// end class
