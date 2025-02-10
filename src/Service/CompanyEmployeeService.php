<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\Employee;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\EntityChangeService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CompanyEmployeeService
{
    private EntityManagerInterface $entityManager;
    private EntityChangeService $changeService;

    public function __construct(
        EntityManagerInterface $entityManager,
        EntityChangeService $changeService
    ) {
        $this->entityManager = $entityManager;
        $this->changeService = $changeService;
    }

    public function createEmployeesForCompany(Company $company, array $employeesData): void
    {
        try {
            $this->entityManager->beginTransaction();

            foreach ($employeesData as $employeeData) {
                $employee = new Employee();
                $this->updateEmployeeFields($employee, $employeeData);
                $employee->setCompany($company);
                $this->entityManager->persist($employee);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    public function updateEmployeeCompany(int $employeeId, int $companyId): bool
    {
        try {
            $employee = $this->findEmployeeOrFail($employeeId);
            $company = $this->findCompanyOrFail($companyId);

            $employee->setCompany($company);

            if ($this->changeService->isEntityDirty($employee)) {
                $this->entityManager->flush();
                return true;
            }

            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getCompanyEmployees(int $companyId): array
    {
        $company = $this->findCompanyOrFail($companyId);
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

        return $data;
    }

    private function findEmployeeOrFail(int $id): Employee
    {
        $employee = $this->entityManager->getRepository(Employee::class)->find($id);
        if (!$employee) {
            throw new BadRequestHttpException("Employee not found");
        }
        return $employee;
    }

    private function findCompanyOrFail(int $id): Company
    {
        $company = $this->entityManager->getRepository(Company::class)->find($id);
        if (!$company) {
            throw new BadRequestHttpException("Company not found");
        }
        return $company;
    }

    private function updateEmployeeFields(Employee $employee, array $data): void
    {
        if (isset($data['firstName'])) {
            $employee->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $employee->setLastName($data['lastName']);
        }
        if (isset($data['email'])) {
            $employee->setEmail($data['email']);
        }
        if (isset($data['phoneNumber'])) {
            $employee->setPhoneNumber($data['phoneNumber']);
        }
    }
}
