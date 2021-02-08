<?php

declare(strict_types=1);

namespace App\QueryLanguage\Validation;

use App\Entity\User;
use Solido\Common\Urn\Urn;
use Solido\QueryLanguage\Expression\Literal\LiteralExpression;
use Solido\QueryLanguage\Expression\ValueExpression;
use Solido\QueryLanguage\Walker\Validation\ValidationWalker;

class UserValidationWalker extends ValidationWalker
{
    /**
     * @inheritDoc
     */
    public function walkLiteral(LiteralExpression $expression)
    {
        $value = (string) $expression;
        if (! Urn::isUrn($value) || (new Urn($value))->class === User::getUrnClass()) {
            return;
        }

        $this->addViolation('"{{ value }}" is not a valid user."', ['{{ value }}' => $value]);
    }

    public function walkComparison(string $operator, ValueExpression $expression)
    {
        if ($operator !== '=' && $operator !== 'like') {
            $this->addViolation('Unsupported operator "{{ operator }}"', ['{{ operator }}' => $operator]);
        }

        return parent::walkComparison($operator, $expression);
    }
}
