<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Status;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\Routing\Requirement\Requirement;

use function ksort;

class ProjectController extends AbstractController
{
    public const ERROR_TITLE = 'Projet inexistant';
    public const ERROR_SHOW = "Impossible d'afficher le projet n°%d car il n'existe pas.";
    public const ERROR_EDIT = "Impossible de modifier le projet n°%d car il n'existe pas.";

    #[Route('/', name: 'project_index')]
    public function index(ProjectRepository $projectRepository): Response
    {
        return $this->render('project/index.html.twig', [
            'projects' => $projectRepository->findAll(),
        ]);
    }

    #[Route('/projet/{id}', name: 'project_show', requirements: ['id' => Requirement::POSITIVE_INT])]
    public function show(ProjectRepository $projectRepository, int $id): Response
    {
        $project = $projectRepository->findOneBy(['id' => $id]);
        if ($project === null) {
            return $this->forward('App\Controller\ErrorController::index', [
                'title' => self::ERROR_TITLE,
                'message' => self::ERROR_SHOW,
                'id' => $id,
            ]);
        }
        $sortedTasks = [];
        $tasks = $project->getTasks();
        foreach ($tasks as $task) {
            $sortedTasks[$task->getStatusId()][] = $task;
        }
        ksort($sortedTasks);

        return $this->render('project/show.html.twig', [
            'project' => $project,
            'tasks' => $sortedTasks,
            'statuses' => Status::getAll(),
        ]);
    }

    #[Route('/projet/creer', name: 'project_create')]
    public function create(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $project = new Project();

        $form = $this->createForm(
            ProjectType::class,
            $project,
            [
                'users' => $project->getUsers()->toArray(),
                'all_users' => $userRepository->findAll(),
            ],
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($project);
            $entityManager->flush();

            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }
        return $this->render(
            'project/add-edit.html.twig',
            [
                'form' => $form,
                'project_name' => '',
            ],
        );
    }

    #[Route('/projet/{id}/modifier1', name: 'project_edit1', requirements: ['id' => Requirement::POSITIVE_INT])]
    #[Route('/projet/{id}/modifier2', name: 'project_edit2', requirements: ['id' => Requirement::POSITIVE_INT])]
    public function edit(Request $request, int $id, EntityManagerInterface $entityManager, ProjectRepository $projectRepository, UserRepository $userRepository): Response
    {
        $project = $projectRepository->findOneBy(['id' => $id]);
        if ($project === null) {
            return $this->forward('App\Controller\ErrorController::index', [
                'title' => self::ERROR_TITLE,
                'message' => self::ERROR_EDIT,
                'id' => $id,
            ]);
        }

        // permet de passer le nom original du projet au template
        $projectCopy = clone $project;

        $form = $this->createForm(
            ProjectType::class,
            $project,
            [
                'users' => $project->getUsers()->toArray(),
                'all_users' => $userRepository->findAll(),
            ],
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($project);
            $entityManager->flush();

            // retourner sur la bonne page en fonction de la route appelée
            if ($request->attributes->get('_route') === 'project_edit1') {
                return $this->redirectToRoute('project_index');
            }
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        return $this->render(
            'project/add-edit.html.twig',
            [
                'form' => $form,
                'project_name' => $projectCopy->getName(),
            ],
        );
    }

    #[Route('/projet/{id}/supprimer', name: 'project_delete', requirements: ['id' => Requirement::POSITIVE_INT])]
    public function delete(Project $project, EntityManagerInterface $entityManager): Response
    {
        // suppression de toutes les tâches du projet
        foreach ($project->getTasks() as $task) {
            $entityManager->remove($task);
        }

        //suppression du projet
        $entityManager->remove($project);
        $entityManager->flush();

        return $this->redirectToRoute('project_index');
    }
}
