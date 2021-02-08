<?php declare(strict_types=1);

namespace App\DTO;

use Kcs\Serializer\Annotation as Serializer;
use Solido\Common\Urn\UrnGeneratorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

trait ResourceTrait
{
    /** @var UrnGeneratorInterface */
    #[Serializer\SerializedName('_id')]
    #[Serializer\Type('urn')]
    public $entity;

    #[Serializer\Exclude]
    private FormFactoryInterface $formFactory;

    /**
     * @required
     */
    public function setFormFactory(FormFactoryInterface $formFactory): void
    {
        $this->formFactory = $formFactory;
    }

    abstract public function commit(): void;
    abstract public function getTypeClass(): string;
    abstract public function create(Request $request): self | FormInterface;
}
