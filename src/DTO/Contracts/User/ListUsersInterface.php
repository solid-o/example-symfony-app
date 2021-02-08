<?php

declare(strict_types=1);

namespace App\DTO\Contracts\User;

use App\Entity;
use Iterator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted(Entity\User::ROLE_ADMIN)
 */
#[Route('/users', methods: [Request::METHOD_GET])]
interface ListUsersInterface
{
    public function __invoke(Request $request): Iterator | FormInterface;
}
