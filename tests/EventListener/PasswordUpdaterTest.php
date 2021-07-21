<?php

declare(strict_types=1);

namespace Tests\EventListener;

use App\Entity\User;
use App\EventListener\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use LogicException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use stdClass;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class PasswordUpdaterTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy | PasswordHasherFactoryInterface $encoderFactory;
    private PasswordUpdater $listener;

    protected function setUp(): void
    {
        $this->encoderFactory = $this->prophesize(PasswordHasherFactoryInterface::class);
        $this->listener = new PasswordUpdater($this->encoderFactory->reveal());
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals([
            Events::prePersist,
            Events::preUpdate,
        ], $this->listener->getSubscribedEvents());
    }

    public function testPreUpdateShouldNotCrashOnNonUserObject(): void
    {
        $args = $this->prophesize(PreUpdateEventArgs::class);
        $args->getObject()->willReturn(new stdClass());
        $args->getEntityManager()->shouldNotBeCalled();

        $this->listener->preUpdate($args->reveal());
    }

    public function testPrePersistShouldNotCrashOnNonUserObject(): void
    {
        $args = $this->prophesize(LifecycleEventArgs::class);
        $args->getObject()->willReturn(new stdClass());
        $this->encoderFactory->getPasswordHasher(Argument::any())->shouldNotBeCalled();

        $this->listener->prePersist($args->reveal());
    }

    public function testPrePersistShouldThrowIfNoPasswordIsSet(): void
    {
        $args = $this->prophesize(LifecycleEventArgs::class);
        $args->getObject()->willReturn(new User('test', 'test@example.org'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Password for user is not set. Maybe a call to user->changePassword was missing?');

        $this->listener->prePersist($args->reveal());
    }

    public function testPreUpdateShouldEncodePasswordIfNeeded(): void
    {
        $user = new User('test', 'test@example.org');
        $user->generateAndResetPassword();

        $args = $this->prophesize(PreUpdateEventArgs::class);
        $args->getObject()->willReturn($user);
        $args->getEntityManager()->willReturn($em = $this->prophesize(EntityManagerInterface::class));

        $em->getUnitOfWork()->willReturn($uow = $this->prophesize(UnitOfWork::class));
        $em->getClassMetadata(User::class)->willReturn($metadata = new ClassMetadata(User::class));
        $uow->recomputeSingleEntityChangeSet($metadata, $user)->shouldBeCalled();

        $this->encoderFactory
            ->getPasswordHasher($user)
            ->willReturn($encoder = $this->prophesize(PasswordHasherInterface::class));

        $encoder->hash(Argument::cetera())->willReturn('encoded_password');
        $this->listener->preUpdate($args->reveal());

        self::assertEquals('encoded_password', $user->getPassword());
    }

    public function testPrePersistShouldEncodePasswordIfNeeded(): void
    {
        $user = new User('test', 'test@example.org');
        $user->generateAndResetPassword();

        $args = $this->prophesize(LifecycleEventArgs::class);
        $args->getObject()->willReturn($user);

        $this->encoderFactory
            ->getPasswordHasher($user)
            ->willReturn($encoder = $this->prophesize(PasswordHasherInterface::class));

        $encoder->hash(Argument::cetera())->willReturn('encoded_password');
        $this->listener->prePersist($args->reveal());

        self::assertEquals('encoded_password', $user->getPassword());
    }
}
