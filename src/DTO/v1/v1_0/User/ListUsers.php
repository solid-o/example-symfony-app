<?php

declare(strict_types=1);

namespace App\DTO\v1\v1_0\User;

use App\DTO\Contracts\User\ListUsersInterface;
use App\DTO\Contracts\User\UserListEntryInterface;
use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Iterator;
use Solido\DtoManagement\InterfaceResolver\ResolverInterface;
use Solido\QueryLanguage\Processor\Doctrine\ORM\Processor;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ListUsers implements ListUsersInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FormFactoryInterface $formFactory,
        private ResolverInterface $resolver
    ) {
    }

    public function __invoke(Request $request): Iterator | FormInterface
    {
        $qb = $this->entityManager
            ->getRepository(Entity\User::class)
            ->createQueryBuilder('u');

        $processor = new Processor($qb, $this->formFactory, [
            'default_page_size' => 20,
            'default_order' => 'email',
        ]);

        $processor
            ->addField('name')
            ->addField('email');

        $itr = $processor->processRequest($request);
        if ($itr instanceof FormInterface) {
            return $itr;
        }

        return $itr->apply(
            fn (Entity\User $user) => $this->resolver->resolve(UserListEntryInterface::class)->get($user)
        );
    }
}
