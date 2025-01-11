<?php

namespace App\Controller;

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CompanyController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/api/company', name: 'create_company', methods: ['POST'])]
    public function createCompany(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Walidacja danych
        if (
            !$data ||
            empty($data['name']) ||
            empty($data['nip']) ||
            empty($data['address']) ||
            empty($data['city']) ||
            empty($data['postalCode'])
        ) {
            return new JsonResponse(['error' => 'All fields are required'], 400);
        }

        $company = new Company();
        $company->setName($data['name']);
        $company->setNip($data['nip']);
        $company->setAddress($data['address']);
        $company->setCity($data['city']);
        $company->setPostalCode($data['postalCode']);

        $entityManager->persist($company);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Company created successfully'], 201);
    }
}
