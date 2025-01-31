<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Company;
use App\Entity\Employee;
use App\Service\EmployeeService;
use App\Service\EntityChangeService;

class EmployeeController extends AbstractController
{
    private EmployeeService $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    #[Route('/api/employee', name: 'create_employee', methods: ['POST'])]
    public function createEmployee(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                throw new BadRequestHttpException('Invalid JSON data');
            }

            $employee = $this->employeeService->createAndSaveEmployee($data);

            return $this->json(
                ['message' => 'Employee created successfully', 'id' => $employee->getId()],
                Response::HTTP_CREATED
            );
        } catch (BadRequestHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/employee', name: 'get_all_employees', methods: ['GET'])]
    public function getAllEmployees(): JsonResponse
    {
        $employees = $this->entityManager->getRepository(Employee::class)->findAll();
        $data = [];

        foreach ($employees as $employee) {
            $data[] = [
                'id' => $employee->getId(),
                'firstName' => $employee->getFirstName(),
                'lastName' => $employee->getLastName(),
                'email' => $employee->getEmail(),
                'phoneNumber' => $employee->getPhoneNumber()
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/employee/{id}', name: 'get_employee_by_id', methods: ['GET'])]
    public function getEmployeeById(int $id): JsonResponse
    {
        $employee = $this->entityManager->getRepository(Employee::class)->find($id);

        if (!$employee)
            return $this->json(['message' => 'Employee not found'], 404);

        $data = [
            'id' => $employee->getId(),
            'firstName' => $employee->getFirstName(),
            'lastName' => $employee->getLastName(),
            'email' => $employee->getEmail(),
            'phoneNumber' => $employee->getPhoneNumber()
        ];

        return $this->json($data);
    }

    #[Route('/api/employee/{id}', name: 'update_employee', methods: ['PUT'])]
    public function updateEmployee(int $id, Request $request, EntityChangeService $entityChangeService): JsonResponse
    {
        try {
            $employee = $this->entityManager->getRepository(Employee::class)->find($id);

            if (!$employee)
                throw new \Exception('Employee not found');

            $this->entityManager->persist($employee);
            $data = json_decode($request->getContent(), true);

            if ($data === null)
                throw new \Exception('Invalid JSON provided');

            if (isset($data['firstName']) && trim($data['firstName']) !== $employee->getFirstName())
                $employee->setFirstName($data['firstName']);

            if (isset($data['lastName']) && trim($data['lastName']) !== $employee->getLastName())
                $employee->setLastName($data['lastName']);

            if (isset($data['email']) && trim($data['email']) !== $employee->getEmail() && filter_var($data['email'], FILTER_VALIDATE_EMAIL))
                $employee->setEmail($data['email']);

            if (isset($data['phoneNumber']) && trim($data['phoneNumber']) !== $employee->getPhoneNumber())
                $employee->setPhoneNumber($data['phoneNumber']);


            if ($entityChangeService->isEntityDirty($employee)) {

                $this->entityManager->flush();

                return $this->json(['message' => 'Employee updated successfully']);
            }

            return $this->json(['message' => 'No changes detected']);
        } catch (\Exception | \InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/api/employee/{id}', name: 'delete_employee', methods: ['DELETE'])]
    public function deleteEmployee(int $id): JsonResponse
    {
        $employee = $this->entityManager->getRepository(Employee::class)->find($id);

        if (!$employee)
            return $this->json(['message' => 'Employee not found'], 404);

        $this->entityManager->remove($employee);
        $this->entityManager->flush();

        return $this->json(['message' => 'Employee deleted successfully']);
    }

    #[Route('/api/employee/{id}/update-company', name: 'update_employee_company', methods: ['PUT'])]
    public function updateEmployeeCompany(int $id, Request $request, EntityChangeService $entityChangeService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Walidacja danych
        if (!$data || !isset($data['company_id']))
            return $this->json(['error' => 'Company ID is required'], 400);

        $employee = $this->entityManager->getRepository(Employee::class)->find($id);

        if (!$employee)
            return $this->json(['error' => 'Employee not found'], 404);

        $company = $this->entityManager->getRepository(Company::class)->find($data['company_id']);

        if (!$company)
            return $this->json(['error' => 'Company not found'], 404);

        $employee->setCompany($company);

        if ($entityChangeService->isEntityDirty($employee)) {
            $this->entityManager->flush();

            return $this->json(['message' => 'Employee\'s company updated successfully']);
        }

        return $this->json(['message' => 'No changes detected']);
    }
}
