<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Solido\Common\Urn\UrnGeneratorInterface;
use Solido\Common\Urn\UrnGeneratorTrait;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use function array_search;
use function array_splice;
use function in_array;

/**
 * @ORM\Entity()
 */
class User implements UserInterface, PasswordHasherAwareInterface, LegacyPasswordAuthenticatedUserInterface, UrnGeneratorInterface
{
    use User\AuthTrait;
    use UrnGeneratorTrait;

    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="uuid")
     */
    private UuidInterface $id;

    /** @ORM\Column(type="datetimetz_immutable") */
    private DateTimeImmutable $createdAt;

    /** @ORM\Column() */
    private string $name;

    /** @ORM\Column() */
    private string $email;

    /**
     * @ORM\Column(type="json")
     *
     * @var string[]
     */
    private array $roles;

    public function __construct(string $name, string $email)
    {
        $this->id = Uuid::uuid4();
        $this->name = $name;
        $this->email = $email;
        $this->roles = [self::ROLE_USER];

        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function addRole(string $role): void
    {
        if (in_array($role, $this->roles, true)) {
            return;
        }

        $this->roles[] = $role;
    }

    public function removeRole(string $role): void
    {
        $index = array_search($role, $this->roles, true);
        if ($index === false) {
            return;
        }

        array_splice($this->roles, $index, 1);
    }

    public function getUrnId(): string
    {
        return (string) $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }
}
