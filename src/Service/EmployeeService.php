<?php

namespace App\Service;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Company;
use App\Entity\Employee;
use App\Service\EntityChangeService;

class EmployeeService
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
     * Tworzy nowego pracownika
     * 
     * @param array $data Dane pracownika
     * @param Company|null $company Firma (opcjonalna)
     * @return Employee Utworzony pracownik
     * @throws BadRequestHttpException gdy dane są nieprawidłowe
     */
    public function createEmployee(array $data, ?Company $company = null): Employee
    {
        // Walidacja danych
        $this->validateEmployeeData($data);

        // Tworzenie pracownika
        $employee = new Employee();
        $this->updateEmployeeFields($employee, $data);

        if ($company !== null) {
            $employee->setCompany($company);
        }

        $this->entityManager->persist($employee);
        // Nie wywołujemy flush() tutaj - pozwalamy na zarządzanie transakcją z zewnątrz

        return $employee;
    }

    /**
     * Tworzy pracownika i od razu zapisuje go w bazie
     * 
     * @param array $data
     * @param Company|null $company
     * @return Employee
     */
    public function createAndSaveEmployee(array $data, ?Company $company = null): Employee
    {
        $employee = $this->createEmployee($data, $company);
        $this->entityManager->flush();
        return $employee;
    }

    private function validateEmployeeData(array $data): void
    {
        // Sprawdzamy wymagane pola
        if (!isset($data['firstName']) || trim($data['firstName']) === '') {
            throw new BadRequestHttpException('Employee first name is required');
        }

        if (!isset($data['lastName']) || trim($data['lastName']) === '') {
            throw new BadRequestHttpException('Employee last name is required');
        }

        if (!isset($data['email']) || trim($data['email']) === '') {
            throw new BadRequestHttpException('Employee email is required');
        }

        // Walidacja formatu email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestHttpException('Invalid email format');
        }
    }

    private function updateEmployeeFields(Employee $employee, array $data): void
    {
        $employee->setFirstName($data['firstName']);
        $employee->setLastName($data['lastName']);
        $employee->setEmail($data['email']);
        $employee->setPhoneNumber($data['phoneNumber'] ?? null);
    }
}
