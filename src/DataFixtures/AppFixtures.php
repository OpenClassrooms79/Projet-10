<?php
// Exécution du script : symfony console doctrine:fixtures:load

namespace App\DataFixtures;

use App\Entity\Contract;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Status;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;

use function array_rand;
use function random_int;
use function sprintf;

class AppFixtures extends Fixture
{
    public const CONTRACTS = ['CDD', 'CDI', 'Freelance'];
    public const NB_ROLES = 2;
    public const NB_USERS = 40;
    public const NB_PROJECTS = 8;
    public const MAX_PROJECT_DAYS = 730;
    public const NB_TASKS = 200;
    public const NB_MAX_USERS_PER_PROJECT = 10;

    private array $contracts = [];
    private array $users = [];
    private array $projects = [];

    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create();

        $this->loadContracts($manager);
        $this->loadUsers($manager, $faker);
        $this->loadProjects($manager, $faker);
        $this->loadTasks($manager, $faker);
    }

    public function loadContracts(ObjectManager $manager): void
    {
        foreach (self::CONTRACTS as $contractName) {
            $contract = new Contract();
            $contract->setName($contractName);
            $manager->persist($contract);
        }
        $manager->flush();

        $this->contracts = $manager->getRepository(Contract::class)->findAll();
    }

    public function loadUsers(ObjectManager $manager, Faker\Generator $faker): void
    {
        for ($i = 1; $i <= self::NB_USERS; $i++) {
            $user = new User();
            $user
                ->setId($i)
                ->setContract($this->contracts[array_rand($this->contracts)])
                ->setFirstName($faker->firstName())
                ->setName($faker->lastName())
                ->setEmail($faker->email())
                ->setEnabled(true)
                ->setRole(random_int(1, self::NB_ROLES))
                ->setPassword($faker->password())
                ->setEntryDate($faker->dateTimeBetween('-10 year'));
            $manager->persist($user);
        }
        $manager->flush();

        $this->users = $manager->getRepository(User::class)->findAll();
    }

    public function loadProjects(ObjectManager $manager, Faker\Generator $faker): void
    {
        for ($i = 1; $i <= self::NB_PROJECTS; $i++) {
            $start = $faker->dateTimeBetween('-5 year');
            $format = sprintf('+%d day', random_int(1, self::MAX_PROJECT_DAYS));
            $end = clone $start;
            $end->modify($format);

            $project = new Project();
            $project
                ->setId($i)
                ->setName('Projet ' . $faker->company())
                ->setArchived(false)
                ->setStartDate($start)
                ->setDeadline($end);

            // ajout d'utilisateurs au projet
            for ($n = 0; $n < min(self::NB_MAX_USERS_PER_PROJECT, self::NB_USERS, random_int(1, self::NB_USERS)); $n++) {
                $project->addUser($this->users[array_rand($this->users)]);
            }

            $manager->persist($project);
        }
        $manager->flush();

        $this->projects = $manager->getRepository(Project::class)->findAll();
    }

    public function loadTasks(ObjectManager $manager, Faker\Generator $faker): void
    {
        for ($i = 1; $i <= self::NB_TASKS; $i++) {
            $task = new Task();
            $statusId = Status::getRandomValue();
            $project = $this->projects[array_rand($this->projects)];
            $userArray = $project->getUsers()->toArray();
            $task
                ->setId($i)
                ->setTitle('Tâche ' . $faker->city())
                ->setDescription($faker->text())
                ->setDeadline($faker->dateTimeBetween('-5 year'))
                ->setProject($project)
                ->setStatusId($statusId);
            if ($statusId !== Status::TO_DO->value) {
                $task->setUser($userArray[array_rand($userArray)]);
            }

            $manager->persist($task);
        }
        $manager->flush();
    }
}
