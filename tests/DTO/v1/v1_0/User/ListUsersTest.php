<?php

declare(strict_types=1);

namespace Tests\DTO\v1\v1_0\User;

use Solido\TestUtils\Symfony\FunctionalTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ListUsersTest extends WebTestCase
{
    use FunctionalTestTrait;

    public function testListUsers(): void
    {
        self::get('/users');
        self::assertResponseIsOk();

        self::get('/users?email=$like(non-existent)');
        self::assertResponseIsOk();
        self::assertResponseHasHeader('X-Total-Count');

        self::assertEquals(0, self::getResponse()->headers->get('X-Total-Count'));
    }

    public function testListUsersShouldReturnBadRequest(): void
    {
        self::get('/users', [
            'X-Order' => 'not-existent, ASC'
        ]);

        self::assertResponseIsBadRequest();
    }
}
