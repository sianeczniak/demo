<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;


use Doctrine\DBAL\Connection;

require_once __DIR__ . '/../../vendor/autoload.php';

class DemoController extends AbstractController
{

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[Route('/api/demo', name: 'app_demo', methods: ['GET'])]
    public function demo(): JsonResponse
    {
        $this->connection->insert('test', [
            'id' => 1,
            'testVar' => 1,
        ]);

        return $this->json(['message' => 'OK']);
    }
}
