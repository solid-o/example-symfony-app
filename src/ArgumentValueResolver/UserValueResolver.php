<?php

declare(strict_types=1);

namespace App\ArgumentValueResolver;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserValueResolver implements ArgumentValueResolverInterface
{
    public function __construct(
    private EntityManagerInterface $entityManager,
    private TokenStorageInterface $tokenStorage
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if ($argument->getType() !== User::class) {
            return false;
        }

        $value = $request->attributes->get($argument->getName(), $request->attributes->get('id'));
        if ($value === null || $value instanceof User) {
            return false;
        }

        if ($value === 'me') {
            return true;
        }

        return Uuid::isValid($value);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $value = $request->attributes->get($argument->getName(), $request->attributes->get('id'));
        if ($value === 'me') {
            $token = $this->tokenStorage->getToken();
            if ($token === null) {
                throw new NotFoundHttpException('Authentication required');
            }

            $currentUser = $token->getUser();
            if (! ($currentUser instanceof User)) {
                throw new NotFoundHttpException('Authentication required');
            }

            yield $currentUser;

            return;
        }

        $user = $this->entityManager->find(User::class, $value);
        if ($user === null) {
            throw new NotFoundHttpException('Cannot find user with given id');
        }

        yield $user;
    }
}
