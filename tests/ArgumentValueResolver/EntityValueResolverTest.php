<?php

declare(strict_types=1);

namespace Tests\ArgumentValueResolver;

use App\ArgumentValueResolver\EntityValueResolver;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function iterator_to_array;

class EntityValueResolverTest extends TestCase
{
    use ProphecyTrait;

    private ManagerRegistry | ObjectProphecy $managerRegistry;
    private EntityValueResolver $resolver;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->prophesize(ManagerRegistry::class);
        $this->resolver = new EntityValueResolver($this->managerRegistry->reveal());
    }

    public function testShouldNotSupportNonExistentClasses(): void
    {
        $argument = new ArgumentMetadata('entity', 'NonExistentClass', false, false, null);
        self::assertFalse($this->resolver->supports(new Request(), $argument));
    }

    public function testShouldNotSupportNonEntitiesClasses(): void
    {
        $argument = new ArgumentMetadata('entity', self::class, false, false, null);
        $this->managerRegistry->getManagerForClass(self::class)->willReturn(null);

        self::assertFalse($this->resolver->supports(new Request(), $argument));
    }

    public function testShouldNotSupportRequestsWithoutIdAttribute(): void
    {
        $argument = new ArgumentMetadata('entity', self::class, false, false, null);
        $this->managerRegistry->getManagerForClass(self::class)->willReturn(new stdClass());

        self::assertFalse($this->resolver->supports(new Request(), $argument));
    }

    public function testShouldSupportRequestsWithIdAttribute(): void
    {
        $argument = new ArgumentMetadata('entity', self::class, false, false, null);
        $this->managerRegistry->getManagerForClass(self::class)->willReturn(new stdClass());

        self::assertTrue($this->resolver->supports(new Request([], [], ['id' => '123']), $argument));
    }

    public function testShouldSearchEntitiesInManagers(): void
    {
        $argument = new ArgumentMetadata('entity', self::class, false, false, null);
        $this->managerRegistry->getManagerForClass(self::class)
            ->willReturn($manager = $this->prophesize(ObjectManager::class));

        $request = new Request([], [], ['id' => '123']);
        $manager->find(self::class, '123')->willReturn(new stdClass());

        self::assertNotEmpty(iterator_to_array($this->resolver->resolve($request, $argument)));
    }

    public function testShouldThrowNotFoundIfEntityWithGivenIdDoesNotExist(): void
    {
        $argument = new ArgumentMetadata('entity', self::class, false, false, null);
        $this->managerRegistry->getManagerForClass(self::class)
            ->willReturn($manager = $this->prophesize(ObjectManager::class));

        $request = new Request([], [], ['id' => '123']);
        $manager->find(self::class, '123')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        iterator_to_array($this->resolver->resolve($request, $argument));
    }

    public function testShouldThrowNotFoundIdConversionFails(): void
    {
        $argument = new ArgumentMetadata('entity', self::class, false, false, null);
        $this->managerRegistry->getManagerForClass(self::class)
            ->willReturn($manager = $this->prophesize(ObjectManager::class));

        $request = new Request([], [], ['id' => '123']);
        $manager->find(self::class, '123')->willThrow(new ConversionException());

        $this->expectException(NotFoundHttpException::class);
        iterator_to_array($this->resolver->resolve($request, $argument));
    }
}
