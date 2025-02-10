<?php

namespace App\Service\Interfaces;

use App\Entity\Company;

interface CompanyManagementInterface
{
    public function findCompanyOrFail(int $id): Company;
}
