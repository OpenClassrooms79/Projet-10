<?php

namespace App\Security\Voter;

use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ProjectVoter extends Voter
{

    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly TaskRepository $taskRepository,
    ) {}

    /**
     * La méthode voteOnAttribute() sera appelée si la méthode supports() renvoie true
     *
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'project_access' || $attribute === 'task_access';
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if ($attribute === 'project_access') {
            $project = $this->projectRepository->find($subject);
        } else {
            $task = $this->taskRepository->find($subject);
            $project = $task?->getProject();
        }

        $user = $token->getUser();

        if (!$user instanceof UserInterface || $project === null) {
            return false; // accès refusé
        }

        return $user->isAdmin() || $project->getUsers()->contains($user);
    }
}