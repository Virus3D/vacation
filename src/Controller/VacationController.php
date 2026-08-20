<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Employee;
use App\Entity\Vacation;
use App\Form\VacationType;
use App\Service\VacationManager;
use App\Repository\VacationRepository;
use Doctrine\ORM\EntityManagerInterface;
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

    /**
     * Управление отпусками.
     */
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
            $label = 'Год ' . $wy['year_number'] .
                ' (' . $wy['start_date']->format('d.m.Y') . ' - ' . $wy['end_date']->format('d.m.Y') . ')';
            $workYearChoices[$label] = $index;
        }

        $form = $this->createForm(
            VacationType::class,
            null,
            ['work_years_choices' => $workYearChoices]
        );
        $form->handleRequest($request);

        $calculationResult = null;

        $allowAdvance = (bool) $form->get('allowAdvance')->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $startDate = $data['startDate'];
            $endDate = $data['endDate'];

            if ($form->get('calculate')->isClicked()) {
                $calculationResult = $this->vacationManager->calculateVacationUsage(
                    $employee,
                    $startDate,
                    $endDate,
                    $allowAdvance
                );
            } elseif ($form->get('save')->isClicked()) {
                if ($data['auto_calculate']) {
                    $result = $this->vacationManager->addVacation($employee, $startDate, $endDate, $allowAdvance);
                    if ($result['success']) {
                        $this->addFlash('success', 'Отпуск успешно добавлен (автоматический расчёт).');
                        return $this->redirectToRoute('app_vacation_show', ['id' => $employee->getId()]);
                    } else {
                        $this->addFlash('error', $result['error']);
                    }
                } else {
                    // Ручное распределение.
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
                            $manualDetails,
                            $allowAdvance
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

    /**
     * Удаление отпуска.
     */
    #[Route('/{id}/delete', name: 'app_vacation_delete', methods: ['POST'])]
    public function delete(Request $request, Vacation $vacation, EntityManagerInterface $entityManager): Response
    {
        $employeeId = $vacation->getEmployee()->getId();

        if ($this->isCsrfTokenValid('delete' . $vacation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($vacation);
            $entityManager->flush();
            $this->addFlash('success', 'Отпуск удалён.');
        } else {
            $this->addFlash('error', 'Недействительный CSRF-токен.');
        }

        return $this->redirectToRoute('app_vacation_show', ['id' => $employeeId]);
    }// end delete()
}// end class
