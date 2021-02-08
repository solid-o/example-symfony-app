<?php

declare(strict_types=1);

namespace App\DTO\Contracts\Task;

use App\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Solido\PatchManager\MergeablePatchableInterface;
use Solido\PatchManager\PatchManagerInterface;
use Solido\Symfony\Annotation\View;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

interface TaskInterface extends MergeablePatchableInterface
{
    /**
     * Task details view.
     * Responds GET /task/{id} requests with user data
     */
    #[Route('/task/{id}', methods: [Request::METHOD_GET])]
    public function get(Entity\Task $task): self;

    /**
     * Creates a task entity and responds with newly created user details.
     * POST /tasks should respond 400 on invalid data or 201
     */
    #[Route('/tasks', methods: [Request::METHOD_POST])]
    #[View(statusCode: Response::HTTP_CREATED)]
    public function create(Request $request): self | FormInterface;

    /**
     * @Security("is_granted('ROLE_ADMIN') or user == task.assignee")
     */
    #[Route('/task/{id}', methods: [Request::METHOD_PATCH])]
    public function edit(Request $request, Entity\Task $task, PatchManagerInterface $patchManager): self;
}
