<?php

declare(strict_types=1);

namespace Tests\DTO\v1\v1_0\User;

use Solido\Common\Urn\Urn;
use Solido\TestUtils\Symfony\FunctionalTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use function base64_encode;
use function json_decode;

use const JSON_THROW_ON_ERROR;

class UserTest extends WebTestCase
{
    use FunctionalTestTrait;

    public function testGetWithId(): void
    {
        self::get('/user/a2c158b8-a351-4d6f-ac21-d1b86e786655');
        self::assertResponseIsOk();

        self::assertJsonResponsePropertyEquals('urn:solido-example::::user:a2c158b8-a351-4d6f-ac21-d1b86e786655', '_id');
        self::assertJsonResponsePropertyEquals(null, 'email');
        self::assertJsonResponsePropertyEquals(null, 'password');
    }

    public function testGetShouldThrowNotFound(): void
    {
        self::get('/user/me');
        self::assertResponseIsNotFound();

        self::get('/user/04341373-55a6-47e6-8d07-b357cf6fb867');
        self::assertResponseIsNotFound();
    }

    public function testGetShouldReturnCurrentUser(): void
    {
        self::get('/user/me', [
            'Authorization' => 'Basic ' . base64_encode('test@example.org:password'),
        ]);

        self::assertResponseIsOk();
        self::assertJsonResponsePropertyEquals('test@example.org', 'email');
        self::assertJsonResponsePropertyEquals(null, 'password');
    }

    public function testUserCanBeCreated(): string
    {
        self::post('/users', [
            'name' => __FUNCTION__,
            'email' => 'test+2021@example.com',
        ], [
            'Authorization' => 'Basic ' . base64_encode('admin@example.org:password'),
        ]);

        self::assertResponseIsCreated();
        self::assertJsonResponsePropertyEquals(__FUNCTION__, 'name');
        self::assertJsonResponsePropertyNotEquals(null, 'password');

        $json = json_decode(self::getResponse()->getContent(), false, 512, JSON_THROW_ON_ERROR);

        return (new Urn($json->_id))->id;
    }

    public function testUserCannotBeCreatedWithoutName(): void
    {
        self::post('/users', ['email' => 'test+invalid@example.com'], [
            'Authorization' => 'Basic ' . base64_encode('admin@example.org:password'),
        ]);

        self::assertResponseIsBadRequest();
    }

    /**
     * @depends testUserCanBeCreated
     */
    public function testUserCanBeEdited(string $id): void
    {
        self::patch('/user/' . $id, [
            'name' => __FUNCTION__,
            'email' => 'test+20212020@example.com',
        ], [
            'Authorization' => 'Basic ' . base64_encode('admin@example.org:password'),
            'Content-Type' => 'application/merge-patch+json',
        ]);

        self::assertResponseIsOk();
    }
}
