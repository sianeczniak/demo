<?php

namespace App\Service;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Company;
use App\Service\EntityChangeService;

class CompanyService
{
    private EntityManagerInterface $entityManager;
    private EntityChangeService $changeService;

    public function __construct(
        EntityManagerInterface $entityManager,
        EntityChangeService $changeService,
    ) {
        $this->entityManager = $entityManager;
        $this->changeService = $changeService;
    }

    /**
     * Tworzy nową firmę wraz z opcjonalnymi pracownikami
     * 
     * @param array $companyData Dane firmy
     * @param array $employeesData Opcjonalne dane pracowników
     * @return Company
     * @throws ValidationFailedException
     */
    public function createCompany(array $companyData): Company
    {
        try {
            $this->validateCompanyData($companyData);

            $company = new Company();
            $this->updateCompanyFields($company, $companyData);

            $this->entityManager->persist($company);
            $this->entityManager->flush();

            return $company;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Aktualizuje dane firmy, śledząc czy nastąpiły rzeczywiste zmiany
     * @param array $data Wybrane właściwości firmy do zaktualizowania 
     * @return bool Informacja o zastosowaniu zmian
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
     * Pobiera wszystkie firmy
     * @return array Firmy
     */
    public function getAllCompanies(): array
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

        return $data;
    }

    /**
     * Pobiera firmę o danym id
     * @param int $id Id szukanej firmy
     * @return array Szukana firma
     */
    public function getCompany(int $id): array
    {
        $company = $this->findCompanyOrFail($id);

        $data = [
            'id' => $company->getId(),
            'name' => $company->getName(),
            'nip' => $company->getNip(),
            'address' => $company->getAddress(),
            'city' => $company->getCity(),
            'postalCode' => $company->getPostalCode(),
        ];

        return $data;
    }

    /**
     * Usuwa firmę o danym id
     * @param int $id Id szukanej firmy
     * @return void
     */
    public function deleteCompany(int $id): void
    {
        try {

            $company = $this->findCompanyOrFail($id);

            $this->entityManager->remove($company);
            $this->entityManager->flush();
        } catch (BadRequestHttpException $e) {
            throw $e;
        }
    }

    /**
     * Pobiera pracowników danej firmy
     * @param int $id Id firmy
     * @return array Pracownicy
     */
    // public function getEmployees(int $id): array
    // {
    //     try {

    //         $company = $this->findCompanyOrFail($id);
    //         $employees = $company->getEmployees();

    //         $data = [];
    //         foreach ($employees as $employee) {
    //             $data[] = [
    //                 'id' => $employee->getId(),
    //                 'firstName' => $employee->getFirstName(),
    //                 'lastName' => $employee->getLastName(),
    //                 'email' => $employee->getEmail(),
    //                 'phoneNumber' => $employee->getPhoneNumber(),
    //             ];
    //         }

    //         return $data;
    //     } catch (BadRequestHttpException $e) {
    //         throw $e;
    //     }
    // }

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

    /**
     * Pomocnicza metoda do zmiany wartości pól encji Company
     */
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

    /**
     * Pomocnicza metoda do znalezienia encji Company o danym id
     */
    public function findCompanyOrFail(int $id): Company
    {
        $company = $this->entityManager->getRepository(Company::class)->find($id);
        if (!$company) {
            throw new BadRequestHttpException("Company with id $id not found");
        }
        return $company;
    }
}
