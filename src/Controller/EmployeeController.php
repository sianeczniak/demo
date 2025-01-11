<?php

namespace App\Controller;

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entity\Employee;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EmployeeController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/api/employee', name: 'create_employee', methods: ['POST'])]
    public function createEmployee(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Walidacja danych 
        if (!$data || !isset($data['firstName'], $data['lastName'], $data['email'])) {
            return $this->json(['error' => 'Invalid data. Required fields: firstName, lastName, email.'], 400);
        }

        $employee = new Employee();
        $employee->setFirstName($data['firstName']);
        $employee->setLastName($data['lastName']);
        $employee->setEmail($data['email']);
        $employee->setPhoneNumber($data['phoneNumber'] ?? null);

        $this->entityManager->persist($employee);
        $this->entityManager->flush();

        return $this->json(['message' => 'Employee created successfully'], 201);
    }
}
