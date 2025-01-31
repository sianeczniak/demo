<?php

namespace App\Controller;

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\EntityChangeService;

class CompanyController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/api/company', name: 'create_company', methods: ['POST'])]
    public function createCompany(Request $request, EmployeeController $employeeController): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Walidacja danych
        if (!$data || !isset($data['name'], $data['nip'], $data['address'], $data['city'], $data['postalCode']))
            return $this->json(['error' => 'Invalid data. All fields of company are required'], 400);

        $company = new Company();
        $company->setName($data['name'])->setNip($data['nip'])->setAddress($data['address'])->setCity($data['city'])->setPostalCode($data['postalCode']);

        $this->entityManager->persist($company);
        $this->entityManager->flush();

        if (isset($data['employees']) && is_array($data['employees'])) {
            foreach ($data['employees'] as $employeeData) {
                $response = $employeeController->createEmployee($employeeData, $company);
                if ($response->getStatusCode() !== 201) {
                    return $this->json(['error' => 'Failed to create employee'], 500);
                }
            }
        }

        return $this->json(['message' => 'Company created successfully'], 201);
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
    public function updateCompany(Request $request, int $id, EntityChangeService $entityChangeService): JsonResponse
    {
        try {
            $company = $this->entityManager->getRepository(Company::class)->find($id);

            if (!$company)
                throw new \Exception('Company not found');

            $this->entityManager->persist($company);
            $data = json_decode($request->getContent(), true);

            if ($data === null)
                throw new \Exception('Invalid JSON provided');

            if (isset($data['name']) && trim($data['name']) !== $company->getName())
                $company->setName($data['name']);
            if (isset($data['nip']) && trim($data['nip']) !== $company->getNip())
                $company->setNip($data['nip']);
            if (isset($data['address']) && trim($data['address']) !== $company->getAddress())
                $company->setAddress($data['address']);
            if (isset($data['city']) && trim($data['city']) !== $company->getCity())
                $company->setCity($data['city']);
            if (isset($data['postalCode']) && trim($data['postalCode']) !== $company->getPostalCode())
                $company->setPostalCode($data['postalCode']);

            if ($entityChangeService->isEntityDirty($company)) {
                $this->entityManager->flush();

                return $this->json(['message' => 'Company updated successfully']);
            }

            return $this->json(['message' => 'No changes detected']);
        } catch (\Exception | \InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
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
