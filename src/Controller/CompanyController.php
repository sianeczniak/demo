<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\CompanyEmployeeService;
use App\Service\CompanyService;

class CompanyController extends AbstractController
{
    private CompanyService $companyService;
    private CompanyEmployeeService $companyEmployeeService;

    public function __construct(CompanyService $companyService, CompanyEmployeeService $companyEmployeeService)
    {
        $this->companyService = $companyService;
        $this->companyEmployeeService = $companyEmployeeService;
    }

    #[Route('/api/company', name: 'create_company', methods: ['POST'])]
    public function createCompany(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $company = $this->companyService->createCompany($data);

            // Jeśli są dane pracowników, tworzymy ich
            if (!empty($data['employees'])) {
                $this->companyEmployeeService->createEmployeesForCompany($company, $data['employees']);
            }

            return $this->json(
                ['message' => 'Company created successfully', 'id' => $company->getId()],
                Response::HTTP_CREATED
            );
        } catch (BadRequestHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/company', name: 'get_all_companies', methods: ['GET'])]
    public function getAllCompanies(): JsonResponse
    {
        $data = $this->companyService->getAllCompanies();
        return $this->json($data);
    }

    #[Route('/api/company/{id}', name: 'get_company', methods: ['GET'])]
    public function getCompany(int $id): JsonResponse
    {
        error_log('Start');
        // $this->entityManager->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
        try {
            $companyData = $this->companyService->getCompany($id);

            return $this->json($companyData);
        } catch (BadRequestHttpException $e) {
            return $this->json(['error' => 'Company not found'], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/api/company/{id}', name: 'update_company', methods: ['PUT'])]
    public function updateCompany(Request $request, int $id): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $data['id'] = $id;

            $hasChanges = $this->companyService->updateCompany($data);

            if ($hasChanges)
                return $this->json(
                    ['message' => 'Company updated successfully', 'id' => $id],
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


    #[Route('/api/company/{id}', name: 'delete_company', methods: ['DELETE'])]
    public function deleteCompany(int $id): JsonResponse
    {
        try {
            $this->companyService->deleteCompany($id);

            return $this->json(['message' => 'Company deleted successfully']);
        } catch (BadRequestHttpException $e) {
            return $this->json(['error' => 'Company not found'], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/api/company/{id}/employees', name: 'get_company_employees', methods: ['GET'])]
    public function getCompanyEmployees(int $id): JsonResponse
    {
        try {
            $employees = $this->companyEmployeeService->getCompanyEmployees($id);
            return $this->json($employees);
        } catch (BadRequestHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
