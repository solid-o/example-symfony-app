<?php

declare(strict_types=1);

namespace Tests\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User('Test User', 'test@example.org');
        (fn (string $id) => $this->id = Uuid::fromString($id))->bindTo($user, User::class)('a2c158b8-a351-4d6f-ac21-d1b86e786655');

        $user->updatePassword('password');
        $manager->persist($user);

        $user = new User('Admin User', 'admin@example.org');
        $user->addRole(User::ROLE_ADMIN);
        (fn (string $id) => $this->id = Uuid::fromString($id))->bindTo($user, User::class)('1bfd6b31-8255-4d25-b3c7-d0022f532aab');

        $user->updatePassword('password');
        $manager->persist($user);

        $manager->flush();
    }
}
