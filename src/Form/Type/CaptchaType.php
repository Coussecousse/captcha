<?php

namespace App\Form\Type;

use App\Domain\AntiSpam\CaptchaInterface;
use App\Domain\AntiSpam\Puzzle\PuzzleChallenge;
use App\Validator\Challenge;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CaptchaType extends AbstractType
{

    public function __construct(private readonly CaptchaInterface $challenge, private readonly UrlGeneratorInterface $urlGenerator) {}

    public function configureOptions(OptionsResolver $resolver): void 
    {
        $resolver->setDefaults([
            'constraints' => [
                new Challenge()
            ], 
            'error_bubbling' => false,
            'route' => 'app_captcha',
        ]);
        parent::configureOptions($resolver);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('challenge', HiddenType::class, [
            'constraints' => [
                new NotBlank(['message' => 'Une erreur est survenue lors de la création du challenge.'])
            ],
            'attr' => [
                'class' => 'captcha-challenge', 
            ],
            'data' => $this->challenge->generateKey()
        ]);
        for ($i = 1; $i <= PuzzleChallenge::PIECES_NUMBER; $i++) {
            $builder->add('answer_'.($i), HiddenType::class, [
                'attr' => [
                    'class' => 'captcha-answer', 
                ]
            ]);
        }

        parent::buildForm($builder, $options);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr'] = [
            'width' => PuzzleChallenge::WIDTH,
            'height' => PuzzleChallenge::HEIGHT,
            'piece-width' => PuzzleChallenge::PIECE_WIDTH,
            'piece-height' => PuzzleChallenge::PIECE_HEIGHT,
            'src' => $this->urlGenerator->generate($options['route'], [
                'challenge' => $form->get('challenge')->getData()
            ]),
            'pieces-number' => PuzzleChallenge::PIECES_NUMBER,
            'space-between-pieces' => PuzzleChallenge::SPACE_BETWEEN_PIECES,
        ];
        
        parent::buildView($view, $form, $options);
    }
}