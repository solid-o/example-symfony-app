<?php

declare(strict_types=1);

namespace Tests\QueryLanguage\Validation;

use App\Entity\User;
use App\QueryLanguage\Validation\UserValidationWalker;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Solido\Common\Urn\Urn;
use Solido\QueryLanguage\Expression\Literal\LiteralExpression;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UserValidationWalkerTest extends TestCase
{
    use ProphecyTrait;

    private ExecutionContextInterface | ObjectProphecy $context;
    private UserValidationWalker $walker;

    protected function setUp(): void
    {
        $this->context = $this->prophesize(ExecutionContextInterface::class);

        $this->walker = new UserValidationWalker();
        $this->walker->setValidationContext($this->context->reveal());
    }

    /**
     * @dataProvider provideValidExpressions
     */
    public function testWalkLiteralValidValues(LiteralExpression $expression): void
    {
        $this->context->buildViolation(Argument::cetera())->shouldNotBeCalled();
        $this->walker->walkLiteral($expression);
    }

    public function provideValidExpressions(): iterable
    {
        Urn::$defaultDomain = 'solido-example';

        yield [LiteralExpression::create('me')];
        yield [LiteralExpression::create('user@example')];
        yield [LiteralExpression::create((string) new Urn('12', User::getUrnClass()))];
        yield [LiteralExpression::create('')];
    }

    public function testWalkLiteralInvalid(): void
    {
        $value = (string) new Urn('12', 'test');
        $this->context
            ->buildViolation('"{{ value }}" is not a valid user."', ['{{ value }}' => $value])
            ->shouldBeCalled()
            ->willReturn($builder = $this->prophesize(ConstraintViolationBuilderInterface::class));

        $builder->addViolation()->shouldBeCalled();
        $this->walker->walkLiteral(LiteralExpression::create($value));
    }

    public function testWalkComparisonValidOperators(): void
    {
        $this->context->buildViolation(Argument::cetera())->shouldNotBeCalled();
        $this->walker->walkComparison('=', LiteralExpression::create('test'));
        $this->walker->walkComparison('like', LiteralExpression::create('test'));
    }

    /**
     * @dataProvider provideInvalidOperators
     */
    public function testWalkComparisonInvalidOperator(string $operator): void
    {
        $this->context
            ->buildViolation('Unsupported operator "{{ operator }}"', ['{{ operator }}' => $operator])
            ->shouldBeCalled()
            ->willReturn($builder = $this->prophesize(ConstraintViolationBuilderInterface::class));

        $this->walker->walkComparison($operator, LiteralExpression::create('test'));
    }

    public function provideInvalidOperators(): iterable
    {
        yield ['>'];
        yield ['>='];
        yield ['<'];
        yield ['<='];
    }
}
