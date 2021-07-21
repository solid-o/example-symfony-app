<?php

declare(strict_types=1);

namespace Tests\QueryLanguage\Walker;

use App\Entity\User;
use App\QueryLanguage\Walker\UserWalker;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Solido\QueryLanguage\Expression\Literal\LiteralExpression;
use Solido\TestUtils\Doctrine\ORM\EntityManagerTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class UserWalkerTest extends TestCase
{
    use EntityManagerTrait;
    use ProphecyTrait;

    private TokenStorageInterface | ObjectProphecy $tokenStorage;
    private QueryBuilder $queryBuilder;
    private UserWalker $walker;

    protected function setUp(): void
    {
        $this->queryBuilder = $this->getEntityManager()
            ->getRepository(User::class)
            ->createQueryBuilder('u');

        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->walker = new UserWalker(
            $this->tokenStorage->reveal(),
            $this->queryBuilder,
            'user'
        );
    }

    private function onEntityManagerCreated(): void
    {
        $driver = new AnnotationDriver(new AnnotationReader());

        $metadata = new ClassMetadata(User::class, $this->_entityManager->getConfiguration()->getNamingStrategy());
        $driver->loadMetadataForClass(User::class, $metadata);

        $this->_entityManager->getMetadataFactory()->setMetadataFor(User::class, $metadata);
    }

    public function testEqualComparisonGenericValue(): void
    {
        self::assertEquals(
            new Comparison('user', '=', ':user'),
            $this->walker->walkComparison('=', LiteralExpression::create('test'))
        );

        self::assertEquals(
            new Parameter('user', 'test', User::class),
            $this->queryBuilder->getParameter('user')
        );
    }

    public function testEqualComparisonWithUuid(): void
    {
        self::assertEquals(
            new Comparison('user', '=', ':user'),
            $this->walker->walkComparison('=', LiteralExpression::create('4a20564b-f42a-4b6c-bdbb-913aea813fc1'))
        );

        self::assertEquals(
            new Parameter('user', '4a20564b-f42a-4b6c-bdbb-913aea813fc1', User::class),
            $this->queryBuilder->getParameter('user')
        );
    }

    public function testEqualComparisonWithMeValue(): void
    {
        $user = new User('Current', 'current@example.org');
        $this->tokenStorage->getToken()
            ->willReturn($token = new PostAuthenticationToken($user, 'main', []));

        self::assertEquals(
            new Comparison('user', '=', ':user'),
            $this->walker->walkComparison('=', LiteralExpression::create('me'))
        );

        $parameter = $this->queryBuilder->getParameter('user');
        self::assertEquals((string) $user->getId(), $parameter->getValue()->getId()->toString());
    }

    public function testEqualComparisonWithEmail(): void
    {
        $this->queryLike('SELECT t0.id AS id_1, t0.created_at AS created_at_2, ' .
            't0.name AS name_3, t0.email AS email_4, t0.roles AS roles_5, t0.password AS password_6, ' .
            't0.salt AS salt_7, t0.encoder AS encoder_8, t0.password_expires_at AS password_expires_at_9 ' .
            'FROM user t0 WHERE t0.email = ? LIMIT 1', ['alekitto@example.org'], [
                [
                    'id_1' => 'c28c62f8-ef32-4758-a5c0-45d8a3603f66',
                    'created_at_2' => '2021-02-07 21:00:00',
                    'email_4' => 'alekitto@example.org',
                ],
            ]);

        self::assertEquals(
            new Comparison('user', '=', ':user'),
            $this->walker->walkComparison('=', LiteralExpression::create('alekitto@example.org'))
        );

        $parameter = $this->queryBuilder->getParameter('user');
        self::assertEquals('c28c62f8-ef32-4758-a5c0-45d8a3603f66', $parameter->getValue()->getId()->toString());
    }

    public function testLikeComparison(): void
    {
        $expr = $this->walker->walkComparison('like', LiteralExpression::create('alex'));
        self::assertEquals(new Comparison('LOWER(user.email)', 'LIKE', ':user_email'), $expr);
        self::assertEquals(new Parameter('user_email', '%alex%'), $this->queryBuilder->getParameter('user_email'));
    }
}
