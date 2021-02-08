<?php

declare(strict_types=1);

namespace App\DTO\Contracts\Task;

use Iterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tasks', methods: [Request::METHOD_GET])]
interface ListTasksInterface
{
    public function __invoke(Request $request): Iterator | FormInterface;
}
