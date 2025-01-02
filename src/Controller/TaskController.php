<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

class TaskController extends AbstractController
{
    public const ERROR_TITLE = 'Tâche inexistante';
    public const ERROR_CREATE = "Impossible de créer la tâche car le projet n°%d n'existe pas.";
    public const ERROR_EDIT = "Impossible de modifier la tâche n°%d car elle n'existe pas.";
    public const ERROR_DELETE = "Impossible de supprimer la tâche n°%d car elle n'existe pas.";

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectRepository $projectRepository,
        private readonly TaskRepository $taskRepository,
    ) {}

    #[Route('/projet/{id}/tache/creer', name: 'task_create')]
    public function create(int $id, Request $request): Response
    {
        $project = $this->projectRepository->findOneBy(['id' => $id]);
        if ($project === null) {
            return $this->forward('App\Controller\ErrorController::index', [
                'title' => ProjectController::ERROR_TITLE,
                'message' => self::ERROR_CREATE,
                'id' => $id,
            ]);
        }
        $task = new Task();

        $form = $this->createForm(TaskType::class, $task, [
            'users' => $project->getUsers()->toArray(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $task->setProject($project);

            $this->entityManager->persist($task);
            $this->entityManager->flush();

            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        return $this->render('task/add-edit.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    #[Route('/tache/{id}/modifier', name: 'task_edit')]
    public function edit(int $id, Request $request): Response
    {
        $task = $this->taskRepository->findOneBy(['id' => $id]);
        if ($task === null) {
            return $this->forward('App\Controller\ErrorController::index', [
                'title' => self::ERROR_TITLE,
                'message' => self::ERROR_EDIT,
                'id' => $id,
            ]);
        }

        $form = $this->createForm(
            TaskType::class,
            $task,
            [
                'users' => $task->getProject()->getUsers()->toArray(),
            ],
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($task);
            $this->entityManager->flush();

            return $this->redirectToRoute('project_show', ['id' => $task->getProject()->getId()]);
        }

        return $this->render('task/add-edit.html.twig', [
            'form' => $form,
            'task' => $task,
        ]);
    }

    #[Route('/tache/{id}/supprimer', name: 'task_delete', requirements: ['id' => Requirement::POSITIVE_INT])]
    public function delete(int $id): Response
    {
        $task = $this->taskRepository->findOneBy(['id' => $id]);
        if ($task === null) {
            return $this->forward('App\Controller\ErrorController::index', [
                'title' => self::ERROR_TITLE,
                'message' => self::ERROR_DELETE,
                'id' => $id,
            ]);
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        $project = $task->getProject();
        if ($project === null) {
            return $this->redirectToRoute('project_index');
        }
        return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
    }
}
