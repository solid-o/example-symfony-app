<?php

declare(strict_types=1);

namespace App\ArgumentValueResolver;

use App\Entity\User;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function assert;
use function class_exists;

class EntityValueResolver implements ArgumentValueResolverInterface
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        $type = $argument->getType();
        if ($type === null || $type === User::class || ! class_exists($type)) {
            return false;
        }

        $manager = $this->managerRegistry->getManagerForClass($type);
        if ($manager === null) {
            return false;
        }

        return $request->attributes->get($argument->getName(), $request->attributes->get('id')) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $argument->getType();
        $value = $request->attributes->get($argument->getName(), $request->attributes->get('id'));

        $manager = $this->managerRegistry->getManagerForClass($type);
        assert($manager !== null);

        try {
            $entity = $manager->find($type, $value);
        } catch (ConversionException $exception) {
            throw new NotFoundHttpException('Cannot find entity with given id', $exception);
        }

        if ($entity === null) {
            throw new NotFoundHttpException('Cannot find entity with given id');
        }

        yield $entity;
    }
}
