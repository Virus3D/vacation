<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Employee;
use App\Form\EmployeeType;
use App\Repository\EmployeeRepository;
use App\Service\VacationManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EmployeeController extends AbstractController
{
    public function __construct(
        private VacationManager $vacationManager
    ) {
    }// end __construct()

    /**
     * Главная страница.
     */
    #[Route('/', name: 'app_employee_index', methods: ['GET'])]
    public function index(EmployeeRepository $employeeRepository, Request $request): Response
    {
        $employees = $employeeRepository->findBy([], ['fullName' => 'ASC']);

        $dateStr = $request->query->get('date', (new DateTime())->format('Y-m-d'));
        $calcDate = DateTime::createFromFormat('Y-m-d', $dateStr);
        if (!$calcDate) {
            $calcDate = new DateTime();
        }

        $remainingDays = $this->vacationManager->getRemainingDaysBatch($employees, $calcDate);

        return $this->render(
            'employee/index.html.twig',
            [
                'employees'     => $employees,
                'remainingDays' => $remainingDays,
                'selectedDate'  => $dateStr,
            ]
        );
    }// end index()

    /**
     * Добавление нового сотрудника.
     */
    #[Route('/employee/new', name: 'app_employee_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $employee = new Employee();
        $form = $this->createForm(EmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($employee);
            $entityManager->flush();

            return $this->redirectToRoute('app_employee_index');
        }

        return $this->render(
            'employee/new.html.twig',
            [
                'employee' => $employee,
                'form'     => $form->createView(),
            ]
        );
    }// end new()

    /**
     * Редактирование сотрудника.
     */
    #[Route('/employee/{id}/edit', name: 'app_employee_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Employee $employee, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_employee_index');
        }

        return $this->render(
            'employee/edit.html.twig',
            [
                'employee' => $employee,
                'form'     => $form->createView(),
            ]
        );
    }// end edit()

    /**
     * Удаление сотрудника.
     */
    #[Route('/employee/{id}', name: 'app_employee_delete', methods: ['POST'])]
    public function delete(Request $request, Employee $employee, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $employee->getId(), $request->request->get('_token'))) {
            $entityManager->remove($employee);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_employee_index');
    }// end delete()
}// end class
