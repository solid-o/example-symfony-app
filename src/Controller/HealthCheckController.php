<?php

declare(strict_types=1);

namespace App\Controller;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Solido\Symfony\Annotation\View;
use Solido\Symfony\Serialization\View\View as SerializationView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class HealthCheckController extends AbstractController
{
    /**
     * @View()
     */
    #[Route('/_healthz', defaults: ['_security' => false])]
    public function getStatusAction(EntityManagerInterface $entityManager): SerializationView
    {
        $statusCode = Response::HTTP_OK;
        $connectionOk = $this->pingConnection($entityManager->getConnection());
        if (! $connectionOk) {
            $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
        }

        return new SerializationView([
            'status' => 'OK',
            'database' => $connectionOk ? 'OK' : 'ERROR',
            'time' => new DateTimeImmutable(),
        ], $statusCode);
    }

    private function pingConnection(Connection $connection): bool
    {
        try {
            $connection->executeStatement($connection->getDatabasePlatform()->getDummySelectSQL());

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
