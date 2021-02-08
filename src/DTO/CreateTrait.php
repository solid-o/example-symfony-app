<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

trait CreateTrait
{
    use ResourceTrait;

    public function create(Request $request): self | FormInterface
    {
        $form = $this->formFactory->createNamed('', $this->getTypeClass(), $this);
        $form->handleRequest($request);
        if (! $form->isValid()) {
            return $form;
        }

        $this->commit();

        return $this->get($this->entity);
    }
}
