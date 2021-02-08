<?php

declare(strict_types=1);

namespace Tests\Controller;

use Solido\TestUtils\Symfony\FunctionalTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class HealthCheckControllerTest extends WebTestCase
{
    use FunctionalTestTrait;

    public function testHealthCheck(): void
    {
        self::get('/_healthz');
        self::assertResponseIsSuccessful();
    }

    public function testHealthCheckWithError(): void
    {
        $previousDbString = $_ENV['DATABASE_URL'];
        try {
            $_ENV['DATABASE_URL'] = 'mysql://user:password@127.0.0.1:33060/db_name?serverVersion=5.7';

            self::get('/_healthz');
            self::assertResponseIs(Response::HTTP_SERVICE_UNAVAILABLE);
        } finally {
            $_ENV['DATABASE_URL'] = $previousDbString;
        }
    }
}
