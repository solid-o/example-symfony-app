<?php

declare(strict_types=1);

namespace Tests\ArgumentValueResolver;

use App\ArgumentValueResolver\UserValueResolver;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use function iterator_to_array;

class UserValueResolverTest extends TestCase
{
    use ProphecyTrait;

    private EntityManagerInterface | ObjectProphecy $entityManager;
    private TokenStorageInterface | ObjectProphecy $tokenStorage;
    private UserValueResolver $resolver;

    protected function setUp(): void
    {
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->resolver = new UserValueResolver($this->entityManager->reveal(), $this->tokenStorage->reveal());
    }

    public function testShouldNotSupportNonExistentClasses(): void
    {
        $argument = new ArgumentMetadata('entity', 'NonExistentClass', false, false, null);
        self::assertFalse($this->resolver->supports(new Request(), $argument));
    }

    public function testShouldNotSupportNonUserClasses(): void
    {
        $argument = new ArgumentMetadata('entity', self::class, false, false, null);
        self::assertFalse($this->resolver->supports(new Request(), $argument));
    }

    public function testShouldNotSupportRequestsWithoutIdAttribute(): void
    {
        $argument = new ArgumentMetadata('entity', self::class, false, false, null);
        self::assertFalse($this->resolver->supports(new Request(), $argument));
    }

    public function testShouldNotSupportRequestsWithNonUuidIdAttribute(): void
    {
        $argument = new ArgumentMetadata('entity', self::class, false, false, null);
        self::assertFalse($this->resolver->supports(new Request([], [], ['id' => '123']), $argument));
    }

    public function testShouldNotSupportRequestsWithUserEntityAttribute(): void
    {
        $argument = new ArgumentMetadata('entity', User::class, false, false, null);
        self::assertFalse($this->resolver->supports(new Request([], [], ['entity' => $this->prophesize(User::class)->reveal()]), $argument));
    }

    public function testShouldSupportRequestsWithUuidIdAttribute(): void
    {
        $argument = new ArgumentMetadata('entity', User::class, false, false, null);
        self::assertTrue($this->resolver->supports(new Request([], [], ['id' => (string) Uuid::uuid4()]), $argument));
    }

    public function testShouldSupportRequestsWithMeIdAttribute(): void
    {
        $argument = new ArgumentMetadata('entity', User::class, false, false, null);
        $user = $this->prophesize(User::class)->reveal();
        $this->tokenStorage->getToken()->willReturn(new PreAuthenticatedToken($user, '', 'main', ['ROLE_USER']));

        self::assertTrue($this->resolver->supports(new Request([], [], ['id' => 'me']), $argument));
    }

    public function testShouldThrowNotFoundIfEntityWithGivenIdDoesNotExist(): void
    {
        $argument = new ArgumentMetadata('entity', User::class, false, false, null);
        $request = new Request([], [], ['id' => '123']);
        $this->entityManager->find(User::class, '123')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        iterator_to_array($this->resolver->resolve($request, $argument));
    }

    public function testShouldYieldUserIfFound(): void
    {
        $argument = new ArgumentMetadata('entity', User::class, false, false, null);
        $request = new Request([], [], ['id' => '123']);
        $this->entityManager->find(User::class, '123')
            ->willReturn($user = $this->prophesize(User::class)->reveal());

        self::assertEquals([$user], iterator_to_array($this->resolver->resolve($request, $argument)));
    }

    public function testShouldYieldUserFromTokenStorageIfIdIsMe(): void
    {
        $argument = new ArgumentMetadata('entity', User::class, false, false, null);
        $request = new Request([], [], ['id' => 'me']);

        $this->entityManager->find(User::class, Argument::any())->shouldNotBeCalled();
        $user = $this->prophesize(User::class)->reveal();
        $this->tokenStorage->getToken()->willReturn(new PreAuthenticatedToken($user, '', 'main', ['ROLE_USER']));

        self::assertEquals([$user], iterator_to_array($this->resolver->resolve($request, $argument)));
    }

    public function testShouldThrowIfMeIsPassedAndNoTokenIsSet(): void
    {
        $argument = new ArgumentMetadata('entity', User::class, false, false, null);
        $request = new Request([], [], ['id' => 'me']);

        $this->entityManager->find(User::class, Argument::any())->shouldNotBeCalled();
        $user = $this->prophesize(User::class)->reveal();
        $this->tokenStorage->getToken()->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        self::assertEquals([$user], iterator_to_array($this->resolver->resolve($request, $argument)));
    }

    public function testShouldThrowIfMeIsPassedAndNoUserIsSetIntoToken(): void
    {
        $argument = new ArgumentMetadata('entity', User::class, false, false, null);
        $request = new Request([], [], ['id' => 'me']);

        $this->entityManager->find(User::class, Argument::any())->shouldNotBeCalled();
        $user = $this->prophesize(User::class)->reveal();
        $this->tokenStorage->getToken()->willReturn(new AnonymousToken('secret', 'anon.'));

        $this->expectException(NotFoundHttpException::class);

        self::assertEquals([$user], iterator_to_array($this->resolver->resolve($request, $argument)));
    }
}
