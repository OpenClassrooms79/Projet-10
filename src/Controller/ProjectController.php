<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Status;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
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

    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly UserRepository $userRepository,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('/projets', name: 'project_index')]
    public function index(): Response
    {
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('welcome_index');
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            // afficher tous les projets non archivés
            return $this->render('project/index.html.twig', [
                'projects' => $this->projectRepository->findBy([
                    'archived' => false,
                ]),
            ]);
        }

        $user = $this->userRepository->find($this->security->getUser()->getId());

        return $this->render('project/index.html.twig', [
            'projects' => $user->getProjects()->filter(function (Project $project) { return !$project->isArchived(); }),
        ]);
    }

    #[Route('/projet/{id}', name: 'project_show', requirements: ['id' => Requirement::POSITIVE_INT])]
    public function show(int $id): Response
    {
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('welcome_index');
        }

        $project = $this->projectRepository->findOneBy(['id' => $id]);
        if ($project === null) {
            return $this->forward('App\Controller\ErrorController::index', [
                'title' => self::ERROR_TITLE,
                'message' => self::ERROR_SHOW,
                'id' => $id,
            ]);
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $ok = true;
        } else {
            $user = $this->userRepository->find($this->security->getUser()->getId());
            $ok = $project->getUsers()->contains($user);
        }

        if ($ok) {
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

        return $this->redirectToRoute('project_index');
    }

    #[Route('/projet/creer', name: 'project_create')]
    public function create(Request $request): Response
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('project_index');
        }

        $project = new Project();

        $form = $this->createForm(
            ProjectType::class,
            $project,
            [
                'users' => $project->getUsers()->toArray(),
                'all_users' => $this->userRepository->findAll(),
            ],
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($project);
            $this->entityManager->flush();

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
    public function edit(Request $request, int $id): Response
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('project_index');
        }

        $project = $this->projectRepository->findOneBy(['id' => $id]);
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
                'all_users' => $this->userRepository->findAll(),
            ],
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($project);
            $this->entityManager->flush();

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
    public function delete(Project $project): Response
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('project_index');
        }

        // suppression de toutes les tâches du projet
        foreach ($project->getTasks() as $task) {
            $this->entityManager->remove($task);
        }

        //suppression du projet
        $this->entityManager->remove($project);
        $this->entityManager->flush();

        return $this->redirectToRoute('project_index');
    }
}
