<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {}

    #[Route('/employes', name: 'user_index')]
    public function index(): Response
    {
        if (!$this->security->isGranted(User::ROLE_ADMIN)) {
            return $this->redirectToRoute('welcome_index');
        }

        return $this->render('user/index.html.twig', [
            'users' => $this->userRepository->findAll(),
        ]);
    }

    #[Route('/employe/{id}/modifier', name: 'user_edit', requirements: ['id' => Requirement::POSITIVE_INT])]
    public function edit(int $id, Request $request): Response
    {
        if (!$this->security->isGranted(User::ROLE_ADMIN)) {
            return $this->redirectToRoute('welcome_index');
        }

        $user = $this->userRepository->findOneBy(['id' => $id]);

        $form = $this->createForm(
            UserType::class,
            $user,
            [
                'roles' => $user->getRoles(),
            ],
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/employe/{id}/supprimer', name: 'user_delete', requirements: ['id' => Requirement::POSITIVE_INT])]
    public function delete(int $id): Response
    {
        if (!$this->security->isGranted(User::ROLE_ADMIN)) {
            return $this->redirectToRoute('welcome_index');
        }

        $user = $this->userRepository->findOneBy(['id' => $id]);
        if ($user !== null) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }
        return $this->redirectToRoute('user_index');
    }
}
