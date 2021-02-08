<?php

declare(strict_types=1);

namespace Tests\Serializer;

use App\Serializer\UrnSerializationHandler;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Solido\Common\Urn\Urn;
use Solido\Common\Urn\UrnGeneratorInterface;

class UrnSerializationHandlerTest extends TestCase
{
    public function testGetType(): void
    {
        self::assertEquals('urn', UrnSerializationHandler::getType());
    }

    public function testSerialize(): void
    {
        Urn::$defaultDomain = 'test-domain';

        $handler = new UrnSerializationHandler();
        $data = $handler->serialize(new class implements UrnGeneratorInterface {
            public function getUrn(): Urn
            {
                $uuid = Uuid::uuid5('9a6b8f87-5f06-419f-b1e8-ade1e76eb111', __FUNCTION__);

                return new Urn((string) $uuid, 'test-class');
            }
        });

        self::assertEquals('urn:test-domain::::test-class:49712e16-a7a4-51d9-b7a6-98941eac92e4', $data);
    }
}
