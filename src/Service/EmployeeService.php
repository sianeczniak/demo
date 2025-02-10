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

    public function getAllEmployees(): array
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
        return $data;
    }

    public function getEmployee(int $id): array
    {
        $employee = $this->findEmployeeOrFail($id);

        $data = [
            'id' => $employee->getId(),
            'firstName' => $employee->getFirstName(),
            'lastName' => $employee->getLastName(),
            'email' => $employee->getEmail(),
            'phoneNumber' => $employee->getPhoneNumber()
        ];

        return $data;
    }

    /**
     * Aktualizuje dane pracownika, śledząc czy nastąpiły rzeczywiste zmiany
     * @param array $data Wybrane właściwości pracownika do zaktualizowania 
     * @return bool Informacja o zastosowaniu zmian
     */
    public function updateEmployee(array $data): bool
    {
        $this->validateEmployeeData($data);

        $company = $this->findEmployeeOrFail($data['id']);

        $this->updateEmployeeFields($company, $data);

        if ($this->changeService->isEntityDirty($company)) {
            $this->entityManager->flush();
            $hasChanges = true;
        } else {
            $hasChanges = false;
        }

        return $hasChanges;
    }

    public function deleteEmployee(int $id): void
    {
        try {
            $employee = $this->findEmployeeOrFail($id);

            $this->entityManager->remove($employee);
            $this->entityManager->flush();
        } catch (BadRequestHttpException $e) {
            throw $e;
        }
    }

    // public function updateEmployeeCompany(array $data): bool
    // {
    //     try {
    //         $employee = $this->findEmployeeOrFail($data['id']);
    //         $company = $this->companyManager->findCompanyOrFail($data['company_id']);

    //         $employee->setCompany($company);

    //         if ($this->changeService->isEntityDirty($employee)) {
    //             $this->entityManager->flush();
    //             $hasChanges = true;
    //         } else {
    //             $hasChanges = false;
    //         }

    //         return $hasChanges;
    //     } catch (BadRequestHttpException $e) {
    //         throw $e;
    //     }
    // }

    private function validateEmployeeData(array $data): void
    {
        $fields = ['firstName', 'lastName', 'email']; // pola obowiązkowe
        if (isset($data['id']) && $data['id']) { // Pracownik istnieje
            foreach ($fields as $field) {
                if (isset($data[$field]) && trim($data[$field]) == '') {
                    throw new BadRequestHttpException("Field $field is required and cannot be empty");
                }
            }
        } else { // Nowy pracownik
            foreach ($fields as $field) {
                if (!isset($data[$field]) || trim($data[$field]) === '') {
                    throw new BadRequestHttpException("Field $field is required and cannot be empty");
                }
            }
        }

        // Walidacja formatu email
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestHttpException('Invalid email format');
        }
    }

    private function updateEmployeeFields(Employee $employee, array $data): void
    {
        if (isset($data['firstName']) && trim($data['firstName']) !== $employee->getFirstName())
            $employee->setFirstName($data['firstName']);
        if (isset($data['lastName']) && trim($data['lastName']) !== $employee->getLastName())
            $employee->setLastName($data['lastName']);
        if (isset($data['email']) && trim($data['email']) !== $employee->getEmail() && filter_var($data['email'], FILTER_VALIDATE_EMAIL))
            $employee->setEmail($data['email']);
        if (isset($data['phoneNumber']) && trim($data['phoneNumber']) !== $employee->getPhoneNumber())
            $employee->setPhoneNumber($data['phoneNumber']);
    }

    /**
     * Pomocnicza metoda do znalezienia encji Employee o danym id
     */
    private function findEmployeeOrFail(int $id): Employee
    {
        $employee = $this->entityManager->getRepository(Employee::class)->find($id);
        if (!$employee) {
            throw new BadRequestHttpException("Employee with id $id not found");
        }
        return $employee;
    }
}
