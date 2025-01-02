<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\LoginType;
use App\Form\RegisterType;
use App\Repository\ContractRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class WelcomeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContractRepository $contractRepository,
        private readonly Security $security,
    ) {}

    #[Route('/', name: 'welcome_index')]
    public function index(): Response
    {
        return $this->render('welcome/index.html.twig', [
            'controller_name' => 'WelcomeController',
        ]);
    }

    #[Route('/inscription', name: 'welcome_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            // User is already logged in
            return $this->redirectToRoute('project_index');
        }

        $user = new User();

        $form = $this->createForm(
            RegisterType::class,
            $user,
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
// hash the password (based on the security.yaml config for the $user class)
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $user->getPassword(),
            );

            $user->setEntryDate(new DateTime('now'));
            $contract = $this->contractRepository->findBy(['name' => 'CDI'])[0];
            $user->setContract($contract);
            $user->setPassword($hashedPassword);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->redirectToRoute('project_index');
        }

        return $this->render('welcome/register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/connexion', name: 'welcome_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            // User is already logged in
            return $this->redirectToRoute('project_index');
        }

        $user = new User();

        $form = $this->createForm(
            LoginType::class,
            $user,
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('project_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('welcome/login.html.twig', [
            'form' => $form,
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/deconnexion', name: 'welcome_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}