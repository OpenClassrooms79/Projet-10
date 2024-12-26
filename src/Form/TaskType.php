<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\User;
use App\Status;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use function array_flip;
use function sprintf;

class TaskType extends AbstractType
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // récupération de la définition de l'entité $class
        $class = $builder->getDataClass();
        $metadata = $this->entityManager->getClassMetadata($class);

        // récupération de la definition du champ "title"
        $titleType = $metadata->getFieldMapping('title');

        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de la tâche',
                'constraints' => [
                    new NotBlank([
                        'allowNull' => false,
                        'message' => 'Le titre est requis.',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Le titre doit avoir au moins {{ limit }} caractères.',
                        'max' => $titleType->length,
                        'maxMessage' => 'Le titre doit avoir au plus {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new NotBlank([
                        'allowNull' => false,
                        'message' => 'La description est requise.',
                    ]),
                    new Length([
                        'min' => 20,
                        'minMessage' => 'La description doit avoir au moins {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('deadline', DateType::class, [
                'label' => 'Date',
                'constraints' => [
                    new NotBlank(['message' => 'La date est requise.']),
                ],
            ])
            ->add('statusId', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => array_flip(Status::getAll()),
                'invalid_message' => 'Le statut sélectionné est invalide.',
            ])->add('user', ChoiceType::class, [
                'label' => 'Membre',
                'placeholder' => '--- Non assigné ---',
                'required' => false,
                'choice_label' => function (User $user) {
                    return $user->getFullName();
                },
                'choices' => $options['users'], // liste des valeurs possibles
                'constraints' => new Callback(function ($value, ExecutionContextInterface $context) {
                    $form = $context->getRoot(); // Récupère les données du formulaire
                    $statusId = $form->get('statusId')->getData(); // Récupère la valeur de 'statusId'

                    if ($statusId !== Status::TO_DO->value && $value === null) {
                        $context
                            ->buildViolation(sprintf('Un utilisateur doit être assigné si la tâche n\'a pas le statut "%s"', Status::getText(Status::TO_DO->value)))
                            ->addViolation();
                    } elseif ($statusId === Status::TO_DO->value && $value !== null) {
                        $context
                            ->buildViolation(sprintf('Aucun utilisateur ne peut être assigné à une tâche au statut "%s"', Status::getText(Status::TO_DO->value)))
                            ->addViolation();
                    }
                }),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
            'users' => [],
        ]);

        $resolver->setAllowedTypes('users', 'array');
    }
}
