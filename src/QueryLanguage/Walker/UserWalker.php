<?php

declare(strict_types=1);

namespace App\QueryLanguage\Walker;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Solido\Common\Urn\Urn;
use Solido\QueryLanguage\Expression\Comparison\LikeExpression;
use Solido\QueryLanguage\Expression\Literal\LiteralExpression;
use Solido\QueryLanguage\Expression\ValueExpression;
use Solido\QueryLanguage\Walker\Doctrine\DqlWalker;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use function filter_var;

use const FILTER_VALIDATE_EMAIL;

class UserWalker extends DqlWalker
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        QueryBuilder $queryBuilder,
        string $field
    ) {
        $this->entityManager = $queryBuilder->getEntityManager();

        parent::__construct($queryBuilder, $field, User::class);
    }

    public function walkComparison(string $operator, ValueExpression $expression): mixed
    {
        if ($operator === '=') {
            $value = (string) $expression;
            if ($value === 'me') {
                $parameterName = $this->generateParameterName();
                $this->queryBuilder->setParameter(
                    $parameterName,
                    $this->tokenStorage->getToken()?->getUser()
                );

                return new Expr\Comparison($this->field, Expr\Comparison::EQ, ':' . $parameterName);
            }

            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $parameterName = $this->generateParameterName();
                $user = $this->entityManager
                    ->getRepository(User::class)
                    ->findOneBy(['email' => $value]);

                $this->queryBuilder->setParameter($parameterName, $user);

                return new Expr\Comparison($this->field, Expr\Comparison::EQ, ':' . $parameterName);
            }

            if (Urn::isUrn($value)) {
                return parent::walkComparison($operator, LiteralExpression::create((new Urn($value))->id));
            }
        } elseif ($operator === 'like') {
            return $this->walkEntry('email', new LikeExpression(LiteralExpression::create((string) $expression)));
        }

        return parent::walkComparison($operator, $expression);
    }
}
