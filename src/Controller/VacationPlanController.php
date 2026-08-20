<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Employee;
use App\Entity\VacationPlan;
use App\Form\VacationPlanType;
use App\Repository\EmployeeRepository;
use App\Repository\VacationPlanRepository;
use App\Service\VacationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vacation-plan')]
class VacationPlanController extends AbstractController
{
    public function __construct(
        private VacationManager $vacationManager,
        private EntityManagerInterface $entityManager
    ) {
    }// end __construct()

    /**
     * Показать план отпусков.
     */
    #[Route('/', name: 'app_vacation_plan_index', methods: ['GET'])]
    public function index(
        VacationPlanRepository $planRepository,
        EmployeeRepository $employeeRepository
    ): Response {
        $plans = $planRepository->findBy([], ['startDate' => 'ASC']);
        $employees = $employeeRepository->findAll();

        return $this->render(
            'vacation_plan/index.html.twig',
            [
                'plans'     => $plans,
                'employees' => $employees,
            ]
        );
    }// end index()

    /**
     * Добавление нового плана отпусков.
     */
    #[Route('/new/{employee}', name: 'app_vacation_plan_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        Employee $employee,
        EntityManagerInterface $entityManager
    ): Response {
        $plan = new VacationPlan();
        $plan->setEmployee($employee);
        $form = $this->createForm(VacationPlanType::class, $plan);
        $form->handleRequest($request);

        $availableDays = null;
        $error = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $startDate = $plan->getStartDate();
            $endDate = $plan->getEndDate();

            if ($endDate < $startDate) {
                $error = 'Дата окончания не может быть раньше даты начала.';
            } else {
                // Рассчитываем доступные дни на дату начала.
                $availableDays = $this->vacationManager->getAvailableDaysForPlanning($employee, $startDate);
                $requestedDays = $startDate->diff($endDate)->days + 1;

                if ($requestedDays > $availableDays['total']) {
                    $error = sprintf(
                        'Недостаточно дней для планирования.
                        Доступно: %d (основной: %d, дополнительный: %d). Запрошено: %d.',
                        $availableDays['total'],
                        $availableDays['main'],
                        $availableDays['additional'],
                        $requestedDays
                    );
                } else {
                    $entityManager->persist($plan);
                    $entityManager->flush();
                    $this->addFlash('success', 'План отпуска сохранён.');
                    return $this->redirectToRoute('app_vacation_plan_index');
                }
            }// end if
        }// end if

        return $this->render(
            'vacation_plan/new.html.twig',
            [
                'form'          => $form->createView(),
                'employee'      => $employee,
                'availableDays' => $availableDays,
                'error'         => $error,
            ]
        );
    }// end new()

    /**
     * Редактирование плана отпуска.
     */
    #[Route('/{id}/edit', name: 'app_vacation_plan_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, VacationPlan $plan, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VacationPlanType::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Повторная проверка доступности при изменении.
            $startDate = $plan->getStartDate();
            $availableDays = $this->vacationManager->getAvailableDaysForPlanning($plan->getEmployee(), $startDate);
            $requestedDays = $startDate->diff($plan->getEndDate())->days + 1;

            if ($requestedDays > $availableDays['total']) {
                $this->addFlash('error', 'Недостаточно дней для планирования.');
            } else {
                $entityManager->flush();
                $this->addFlash('success', 'План обновлён.');
                return $this->redirectToRoute('app_vacation_plan_index');
            }
        }

        return $this->render(
            'vacation_plan/edit.html.twig',
            [
                'form' => $form->createView(),
                'plan' => $plan,
            ]
        );
    }// end edit()

    /**
     * Удаление плана отпуска.
     */
    #[Route('/{id}', name: 'app_vacation_plan_delete', methods: ['POST'])]
    public function delete(Request $request, VacationPlan $plan, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $plan->getId(), $request->request->get('_token'))) {
            $entityManager->remove($plan);
            $entityManager->flush();
            $this->addFlash('success', 'План удалён.');
        }
        return $this->redirectToRoute('app_vacation_plan_index');
    }// end delete()

    /**
     * Выбор сотрудника для плана.
     */
    #[Route('/select-employee', name: 'app_vacation_plan_new_select', methods: ['GET'])]
    public function selectEmployee(Request $request): Response
    {
        $employeeId = $request->query->get('employee_id');
        if (!$employeeId) {
            $this->addFlash('error', 'Выберите сотрудника.');
            return $this->redirectToRoute('app_vacation_plan_index');
        }
        return $this->redirectToRoute('app_vacation_plan_new', ['employee' => $employeeId]);
    }// end selectEmployee()
}// end class
