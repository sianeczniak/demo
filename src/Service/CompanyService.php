<?php

namespace App\Service;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Company;
use App\Service\EmployeeService;
use App\Service\EntityChangeService;

class CompanyService
{
    private EntityManagerInterface $entityManager;
    private EntityChangeService $changeService;
    private EmployeeService $employeeService;

    public function __construct(
        EntityManagerInterface $entityManager,
        EntityChangeService $changeService,
        EmployeeService $employeeService,
    ) {
        $this->entityManager = $entityManager;
        $this->changeService = $changeService;
        $this->employeeService = $employeeService;
    }

    /**
     * Tworzy nową firmę wraz z opcjonalnymi pracownikami
     * 
     * @param array $companyData Dane firmy
     * @param array $employeesData Opcjonalne dane pracowników
     * @return Company
     * @throws ValidationFailedException
     */
    public function createCompany(array $companyData, array $employeesData = []): Company
    {
        try {
            $this->validateCompanyData($companyData);

            // Rozpoczynamy transakcję
            $this->entityManager->beginTransaction();

            $company = new Company();
            $this->updateCompanyFields($company, $companyData);

            $this->entityManager->persist($company);

            if (!empty($employeesData)) {
                foreach ($employeesData as $employeeData) {
                    $this->employeeService->createEmployee($employeeData, $company);
                }
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            return $company;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * Aktualizuje dane firmy, śledząc czy nastąpiły rzeczywiste zmiany
     */
    public function updateCompany(array $data): bool
    {
        $this->validateCompanyData($data);

        $company = $this->findCompanyOrFail($data['id']);

        $this->updateCompanyFields($company, $data);

        if ($this->changeService->isEntityDirty($company)) {
            $this->entityManager->flush();
            $hasChanges = true;
        } else {
            $hasChanges = false;
        }

        return $hasChanges;
    }

    /**
     * Pomocnicza metoda do walidacji danych firmy
     */
    private function validateCompanyData(array $data): void
    {
        $fields = ['name', 'nip', 'address', 'city', 'postalCode'];

        if (isset($data['id']) && $data['id']) { // Firma istnieje

            foreach ($fields as $field) {
                if (isset($data[$field]) && trim($data[$field]) == '') {
                    throw new BadRequestHttpException("Field $field is required and cannot be empty");
                }
            }
        } else { // Nowa firma
            foreach ($fields as $field) {
                if (!isset($data[$field]) || trim($data[$field]) === '') {
                    throw new BadRequestHttpException("Field $field is required and cannot be empty");
                }
            }
        }
    }

    private function updateCompanyFields(Company $company, array $data): void
    {
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
    }

    private function findCompanyOrFail(int $id): Company
    {
        $company = $this->entityManager->getRepository(Company::class)->find($id);
        if (!$company) {
            throw new BadRequestHttpException("Company with id $id not found");
        }
        return $company;
    }
}
