<?php

namespace App\Service\Interfaces;

use App\Entity\Employee;
use App\Entity\Company;

interface EmployeeManagementInterface
{
    public function createEmployee(array $data, ?Company $company = null): Employee;
}
