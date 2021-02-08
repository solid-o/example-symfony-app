<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Refugis\DoctrineExtra\ORM\Timestampable\TimestampableTrait;
use Refugis\DoctrineExtra\Timestampable\TimestampableInterface;
use Solido\Common\Urn\UrnGeneratorInterface;
use Solido\Common\Urn\UrnGeneratorTrait;

/**
 * @ORM\Entity()
 */
class Task implements TimestampableInterface, UrnGeneratorInterface
{
    use TimestampableTrait;
    use UrnGeneratorTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="uuid")
     */
    private UuidInterface $id;

    /** @ORM\Column() */
    private string $title;

    /** @ORM\Column(type="text") */
    private string $description;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private User $assignee;

    /** @ORM\Column(type="datetimetz_immutable", nullable=true) */
    private ?DateTimeInterface $dueDate;

    public function __construct(string $title, User $assignee)
    {
        $this->id = Uuid::uuid4();
        $this->title = $title;
        $this->description = '';
        $this->assignee = $assignee;
        $this->dueDate = null;

        $this->createdAt = new DateTimeImmutable();
        $this->updateTimestamp();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getAssignee(): User
    {
        return $this->assignee;
    }

    public function setAssignee(User $assignee): void
    {
        $this->assignee = $assignee;
    }

    public function getDueDate(): ?DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?DateTimeInterface $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    public function getUrnId(): string
    {
        return (string) $this->id;
    }
}
