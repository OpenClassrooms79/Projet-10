<?php

namespace App\Form;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProjectType extends AbstractType
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

        // récupération de la definition du champ "name"
        $nameType = $metadata->getFieldMapping('name');

        $builder
            ->add('name', TextType::class, [
                'label' => 'Titre du projet',
                'constraints' => [
                    new NotBlank([
                        'allowNull' => false,
                        'message' => 'Le titre est requis.',
                    ]),
                    new Length([
                        'min' => 5,
                        'minMessage' => 'Le titre doit avoir au moins {{ limit }} caractères.',
                        'max' => $nameType->length,
                        'maxMessage' => 'Le titre doit avoir au plus {{ limit }} caractères.',
                    ]),
                ],
            ])->add('users', ChoiceType::class, [
                'label' => 'Inviter des membres',
                'multiple' => true,
                'required' => true,
                'choice_label' => function ($user) {
                    return $user->getFullName(); // le nom complet de l'utilisateur est utilisé comme intitulé pour la balise <option>
                },
                'choice_value' => function ($user) {
                    return $user->getId(); // l'id de l'utilisateur est utilisé comme valeur pour la balise <option>
                },
                'choices' => $options['all_users'], // liste des valeurs possibles
                'invalid_message' => 'Au moins un employé sélectionné était invalide',
                'data' => $options['users'], // valeurs par défaut
                'constraints' => [
                    new Choice([
                        'min' => 1,
                        'minMessage' => 'Vous devez affecter au moins un utilisateur au projet',
                        'multiple' => true,
                        'choices' => $options['all_users'],
                        'multipleMessage' => 'La valeur {{ value }} est invalide',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'all_users' => [],
            'users' => [],
        ]);

        $resolver->setAllowedTypes('all_users', 'array');
        $resolver->setAllowedTypes('users', 'array');
    }
}
