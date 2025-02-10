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
use App\Service\CompanyEmployeeService;
use App\Service\EmployeeService;
use App\Service\EntityChangeService;

class EmployeeController extends AbstractController
{
    private EmployeeService $employeeService;
    private CompanyEmployeeService $companyEmployeeService;

    public function __construct(EmployeeService $employeeService, CompanyEmployeeService $companyEmployeeService)
    {
        $this->employeeService = $employeeService;
        $this->companyEmployeeService = $companyEmployeeService;
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

        $employeesData = $this->employeeService->getAllEmployees();
        return $this->json($employeesData);
    }

    #[Route('/api/employee/{id}', name: 'get_employee', methods: ['GET'])]
    public function getEmployee(int $id): JsonResponse
    {
        try {
            $employeeData = $this->employeeService->getEmployee($id);
            return $this->json($employeeData);
        } catch (BadRequestHttpException $e) {
            return $this->json(['error' => 'Employee not found'], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/api/employee/{id}', name: 'update_employee', methods: ['PUT'])]
    public function updateEmployee(Request $request, int $id): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $data['id'] = $id;

            $hasChanges = $this->employeeService->updateEmployee($data);

            if ($hasChanges)
                return $this->json(
                    ['message' => 'Employee updated successfully', 'id' => $id],
                    Response::HTTP_OK
                );
            else
                return $this->json(
                    ['message' => 'No changes detected', 'id' => $id],
                    Response::HTTP_NOT_MODIFIED
                );
        } catch (BadRequestHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/employee/{id}', name: 'delete_employee', methods: ['DELETE'])]
    public function deleteEmployee(int $id): JsonResponse
    {
        try {
            $this->employeeService->deleteEmployee($id);
            return $this->json(['message' => 'Employee deleted successfully']);
        } catch (BadRequestHttpException $e) {
            return $this->json(['error' => 'Employee not found'], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/api/employee/{id}/update-company', name: 'update_employee_company', methods: ['PUT'])]
    public function updateEmployeeCompany(Request $request, int $id): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $hasChanges = $this->companyEmployeeService->updateEmployeeCompany($id, $data['company_id']);

            if ($hasChanges)
                return $this->json(['message' => 'Employee\'s company updated successfully', 'id' => $id], response::HTTP_OK);
            else
                return $this->json(['message' => 'No changes detected', 'id' => $id], Response::HTTP_NOT_MODIFIED);
        } catch (BadRequestHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
