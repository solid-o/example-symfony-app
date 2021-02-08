<?php declare(strict_types=1);

namespace App\DTO\v1\v1_0\Task;

use App\DTO\Contracts\Task\TaskInterface;
use App\DTO\CreateTrait;
use App\DTO\ResourceTrait;
use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Kcs\Serializer\Annotation as Serializer;
use Solido\PatchManager\PatchManagerInterface;
use Solido\Symfony\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class Task implements TaskInterface
{
    use CreateTrait;
    use ResourceTrait;

    /** @var string */
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    public $title;

    /** @var string */
    public $description;

    /** @var Entity\User */
    #[Assert\NotNull]
    #[Serializer\Type('urn')]
    private $assignee;

    public function __construct(
        #[Serializer\Exclude]
        private EntityManagerInterface $entityManager
    ) {
    }

    public function get(Entity\Task $task): self
    {
        $this->entity = $task;
        $this->title = $task->getTitle();
        $this->description = $task->getDescription();
        $this->assignee = $task->getAssignee();

        return $this;
    }

    public function getAssignee(): Entity\User
    {
        return $this->assignee;
    }

    #[Security('is_granted(\''.Entity\User::ROLE_ADMIN.'\') or user == assignee')]
    public function setAssignee(Entity\User $assignee): void
    {
        $this->assignee = $assignee;
    }

    public function edit(Request $request, Entity\Task $task, PatchManagerInterface $patchManager): self
    {
        $patchManager->patch($this->get($task), $request);

        return $this->get($this->entity);
    }

    public function getTypeClass(): string
    {
        return TaskType::class;
    }

    public function commit(): void
    {
        if ($this->entity === null) {
            $this->entity = new Entity\Task($this->title, $this->assignee);
            $this->entityManager->persist($this->entity);
        } else {
            $this->entity->setTitle($this->title);
            $this->entity->setAssignee($this->assignee);
        }

        $this->entity->setDescription($this->description);
        $this->entityManager->flush();
    }
}
