<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use LogicException;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class PasswordUpdater implements EventSubscriber
{
    public function __construct(
    private EncoderFactoryInterface $encoderFactory
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();
        if (! $entity instanceof User) {
            return;
        }

        if ($entity->shouldEncodePassword() === false) {
            return;
        }

        $this->encode($entity);

        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(User::class), $entity);
    }

    public function prePersist(LifecycleEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();
        if (! $entity instanceof User) {
            return;
        }

        if ($entity->shouldEncodePassword() === false) {
            throw new LogicException('Password for user is not set. Maybe a call to user->changePassword was missing?');
        }

        $this->encode($entity);
    }

    /**
     * Store the encoded password into the entity.
     */
    private function encode(User $entity): void
    {
        $encoder = $this->encoderFactory->getEncoder($entity);

        $entity->setPassword($encoder->encodePassword($entity->getPlainPassword(), $entity->getSalt()));
        $entity->eraseCredentials();
    }
}
