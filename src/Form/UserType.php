<?php

namespace App\Form;

use App\Entity\Contract;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
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

        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prenom',
                'constraints' => [
                    new NotBlank([
                        'allowNull' => false,
                        'message' => 'Le prénom est requis.',
                    ]),
                    new Length([
                        'max' => $metadata->getFieldMapping('firstName')->length,
                        'maxMessage' => 'Le prénom doit avoir au plus {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank([
                        'allowNull' => false,
                        'message' => 'Le nom est requis.',
                    ]),
                    new Length([
                        'max' => $metadata->getFieldMapping('name')->length,
                        'maxMessage' => 'Le nom doit avoir au plus {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank([
                        'allowNull' => false,
                        'message' => 'L\'adresse e-mail est requise.',
                    ]),
                    new Length([
                        'max' => $metadata->getFieldMapping('email')->length,
                        'maxMessage' => 'L\'adresse e-mail doit avoir au plus {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('entryDate', null, [
                'widget' => 'single_text',
                'label' => 'Date d\'entrée',
                'constraints' => [
                    new NotBlank(['message' => 'La date est requise.']),
                ],
            ])
            ->add('contract', EntityType::class, [
                'class' => Contract::class,
                'label' => 'Contrat',
                'choice_label' => 'name',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
