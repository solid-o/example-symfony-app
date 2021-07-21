<?php

declare(strict_types=1);

namespace App\DTO\v1\v1_1\Task;

use App\DTO\Contracts\Task\ListTasksInterface;
use App\DTO\Contracts\Task\TaskInterface;
use App\Entity;
use App\QueryLanguage\Validation\UserValidationWalker;
use App\QueryLanguage\Walker\UserWalker;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Iterator;
use Solido\DtoManagement\InterfaceResolver\ResolverInterface;
use Solido\QueryLanguage\Processor\Doctrine\ORM\Processor;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ListTasks implements ListTasksInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FormFactoryInterface $formFactory,
        private ResolverInterface $resolver,
        private AuthorizationCheckerInterface $authorizationChecker,
        private TokenStorageInterface $tokenStorage
    ) {
    }

    public function __invoke(Request $request): Iterator | FormInterface
    {
        $qb = $this->entityManager
            ->getRepository(Entity\Task::class)
            ->createQueryBuilder('t');

        $processor = new Processor($qb, $this->formFactory, [
            'default_page_size' => 20,
            'default_order' => 'email',
        ]);

        if (! $this->authorizationChecker->isGranted(Entity\User::ROLE_ADMIN)) {
            $qb->andWhere('t.assignee = :currentUser')
                ->setParameter('currentUser', $this->tokenStorage->getToken()->getUser());
        }

        $processor
            ->addField('title')
            ->addField('description')
            ->addField('assignee', [
                'walker' => fn (QueryBuilder $queryBuilder, string $fieldName) => new UserWalker($this->tokenStorage, $queryBuilder, $fieldName),
                'validation_walker' => UserValidationWalker::class,
            ])
            ->addField('due_date', ['field_name' => 'dueDate']);

        $itr = $processor->processRequest($request);
        if ($itr instanceof FormInterface) {
            return $itr;
        }

        return $itr->apply(
            fn (Entity\Task $task) => $this->resolver->resolve(TaskInterface::class)->get($task)
        );
    }
}
