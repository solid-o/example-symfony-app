<?php declare(strict_types=1);

namespace App\DTO\v1\v1_0\User;

use App\DTO\Contracts\User\UserInterface;
use App\DTO\CreateTrait;
use App\DTO\ResourceTrait;
use App\Entity;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Kcs\Serializer\Annotation as Serializer;
use Solido\PatchManager\PatchManagerInterface;
use Solido\Symfony\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class User implements UserInterface
{
    use CreateTrait;
    use ResourceTrait;

    /** @var string */
    #[Assert\NotBlank]
    public $name;

    /** @var string */
    #[Assert\NotBlank]
    #[Assert\Email]
    private $email;

    /** @var string */
    public $password;

    public function __construct(
        #[Serializer\Exclude]
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function get(Entity\User $user): self
    {
        $this->entity = $user;
        $this->name = $user->getName();
        $this->email = $user->getEmail();

        return $this;
    }

    #[Serializer\VirtualProperty]
    public function getCreation(): DateTimeInterface
    {
        return $this->entity->getCreatedAt();
    }

    #[Security('user == object.entity or is_granted(\''.Entity\User::ROLE_ADMIN.'\')', onInvalid: Security::RETURN_NULL)]
    public function getEmail(): ?string
    {
        return $this->email;
    }

    #[Security('is_granted(\''.Entity\User::ROLE_ADMIN.'\')')]
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getTypeClass(): string
    {
        return UserType::class;
    }

    public function commit(): void
    {
        if ($this->entity === null) {
            $this->entity = new Entity\User($this->name, $this->email);
            $this->password = $this->entity->generateAndResetPassword();

            $this->entityManager->persist($this->entity);
        } else {
            $this->entity->setName($this->name);
            $this->entity->setEmail($this->email);
        }

        $this->entityManager->flush();
    }

    public function edit(Request $request, Entity\User $user, PatchManagerInterface $patchManager): UserInterface
    {
        $patchManager->patch($this->get($user), $request);

        return $this;
    }
}
