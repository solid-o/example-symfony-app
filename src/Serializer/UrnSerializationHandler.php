<?php

declare(strict_types=1);

namespace App\Serializer;

use Kcs\Serializer\Handler\SerializationHandlerInterface;
use Solido\Common\Urn\UrnGeneratorInterface;

use function assert;

class UrnSerializationHandler implements SerializationHandlerInterface
{
    public static function getType(): string
    {
        return 'urn';
    }

    public function serialize(mixed $data): string
    {
        assert($data instanceof UrnGeneratorInterface);

        return (string) $data->getUrn();
    }
}
