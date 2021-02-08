<?php

declare(strict_types=1);

namespace App\DTO\Contracts\User;

use App\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Solido\PatchManager\MergeablePatchableInterface;
use Solido\PatchManager\PatchManagerInterface;
use Solido\Symfony\Annotation\View;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

interface UserInterface extends MergeablePatchableInterface
{
    /**
     * User details view.
     * Responds GET /user/{id} requests with user data
     */
    #[Route('/user/{id}', methods: [Request::METHOD_GET])]
    public function get(User $user): self;

    /**
     * Creates a user entity and responds with newly created user details.
     * POST /users should respond 400 on invalid data or 201
     *
     * @IsGranted(User::ROLE_ADMIN)
     */
    #[Route('/users', methods: [Request::METHOD_POST])]
    #[View(statusCode: Response::HTTP_CREATED)]
    public function create(Request $request): self | FormInterface;

    /**
     * @IsGranted(User::ROLE_ADMIN)
     */
    #[Route('/user/{id}', methods: [Request::METHOD_PATCH])]
    public function edit(Request $request, User $user, PatchManagerInterface $patchManager): self;
}
