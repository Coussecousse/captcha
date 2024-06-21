<?php

namespace App\Form;

use App\Entity\User;
use App\Form\Type\CaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use App\Validator\Challenge;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options):void {
        $builder
            ->add('username', TextType::class, [
                'required' => false, 
                'attr' => [ 
                    'class' => 'login__input',
                ],
                'constraints' => [
                    new NotBlank(),
                ]
            ])
            ->add('password', PasswordType::class, [
              'required' => true,
              'attr' => [
                'class' => 'login__input',
              ]
            ])
            ->add('captcha', CaptchaType::class, [
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
} 