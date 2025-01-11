<?php

namespace App\Controller;

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entity\Employee;
use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\EntityChangeService;

class EmployeeController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/api/employee', name: 'create_employee', methods: ['POST'])]
    public function createEmployeeFromRequest(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data)
            return $this->json(['error' => 'Invalid JSON data for creating employee'], 400);

        return $this->createEmployee($data);
    }

    // chętnie użyłabym typu mixed zamiast array, jednak ogranicza mnie Symfony i jego sposób rozpoznawania typu zmiennych
    public function createEmployee(array $data, Company $company = null): JsonResponse
    {
        // Walidacja danych
        if (!$data || !is_array($data) || !isset($data['firstName'], $data['lastName'], $data['email']))
            return $this->json(['error' => 'Invalid data. Required fields for employee: firstName, lastName, email.'], 400);

        $employee = new Employee();
        $employee->setFirstName($data['firstName']);
        $employee->setLastName($data['lastName']);
        $employee->setEmail($data['email']);
        $employee->setPhoneNumber($data['phoneNumber'] ?? null);

        if ($company !== null)
            $employee->setCompany($company);

        $this->entityManager->persist($employee);
        $this->entityManager->flush();

        return $this->json(['message' => 'Employee created successfully'], 201);
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
