<?php

declare(strict_types=1);

namespace App\DTO\v1\v1_0\User;

use App\DTO\Contracts\User\UserListEntryInterface;
use App\Entity;
use DateTimeInterface;
use Kcs\Serializer\Annotation as Serializer;

class UserListEntry implements UserListEntryInterface
{
    /** @var Entity\User */
    #[Serializer\SerializedName('_id')]
    #[Serializer\Type('urn')]
    public $entity;

    /** @var string */
    public $name;

    /** @var string */
    public $email;

    /** @var DateTimeInterface */
    public $creation;

    public function get(Entity\User $user): self
    {
        $this->entity = $user;
        $this->name = $user->getName();
        $this->email = $user->getEmail();
        $this->creation = $user->getCreatedAt();

        return $this;
    }
}
