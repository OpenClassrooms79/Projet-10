<?php

namespace App\Form;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginType extends AbstractType
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
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'data' => $options['last_username'], // dernière adresse e-mail saisie dans le formulaire
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
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'allowNull' => false,
                        'message' => 'Le mot de passe est requis.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'last_username' => '',
        ]);
    }
}
