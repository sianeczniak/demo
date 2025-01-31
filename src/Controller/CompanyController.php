<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Company;
use App\Service\CompanyService;
use App\Service\EntityChangeService;

class CompanyController extends AbstractController
{
    private CompanyService $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    #[Route('/api/company', name: 'create_company', methods: ['POST'])]
    public function createCompany(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $company = $this->companyService->createCompany(
                $data,
                $data['employees'] ?? []
            );

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
        $companies = $this->entityManager->getRepository(Company::class)->findAll();
        $data = [];

        foreach ($companies as $company) {
            $data[] = [
                'id' => $company->getId(),
                'name' => $company->getName(),
                'nip' => $company->getNip(),
                'address' => $company->getAddress(),
                'city' => $company->getCity(),
                'postalCode' => $company->getPostalCode(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/company/{id}', name: 'get_company', methods: ['GET'])]
    public function getCompany(int $id): JsonResponse
    {
        $company = $this->entityManager->getRepository(Company::class)->find($id);

        if (!$company)
            return $this->json(['message' => 'Company not found'], 404);

        $data = [
            'id' => $company->getId(),
            'name' => $company->getName(),
            'nip' => $company->getNip(),
            'address' => $company->getAddress(),
            'city' => $company->getCity(),
            'postalCode' => $company->getPostalCode(),
        ];

        return $this->json($data);
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
        $company = $this->entityManager->getRepository(Company::class)->find($id);

        if (!$company)
            return $this->json(['message' => 'Company not found'], 404);

        $this->entityManager->remove($company);
        $this->entityManager->flush();

        return $this->json(['message' => 'Company deleted successfully']);
    }

    #[Route('/api/company/{id}/employees', name: 'get_company_employees', methods: ['GET'])]
    public function getEmployees(int $id): JsonResponse
    {
        $company = $this->entityManager->find(Company::class, $id);

        if (!$company)
            return $this->json(['error' => 'Company not found'], 404);

        $employees = $company->getEmployees();

        $data = [];
        foreach ($employees as $employee) {
            $data[] = [
                'id' => $employee->getId(),
                'firstName' => $employee->getFirstName(),
                'lastName' => $employee->getLastName(),
                'email' => $employee->getEmail(),
                'phoneNumber' => $employee->getPhoneNumber(),
            ];
        }

        return $this->json($data);
    }
}
