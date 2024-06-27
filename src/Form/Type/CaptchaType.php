<?php

namespace App\Form\Type;

use App\API\CaptchaGeneratorInterface;
use App\API\Puzzle\PuzzleGenerator;
use App\Validator\Captcha;
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

    public function __construct(private readonly CaptchaGeneratorInterface $challenge, private readonly UrlGeneratorInterface $urlGenerator) {}

    public function configureOptions(OptionsResolver $resolver): void 
    {
        $resolver->setDefaults([
            'constraints' => [
                new Captcha()
            ], 
            'error_bubbling' => false,
            // 'route' => 'app_captcha',
            'route' => 'app_captcha_api',
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
        for ($i = 1; $i <= PuzzleGenerator::PIECES_NUMBER; $i++) {
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
            'width' => PuzzleGenerator::WIDTH,
            'height' => PuzzleGenerator::HEIGHT,
            'piece-width' => PuzzleGenerator::PIECE_WIDTH,
            'piece-height' => PuzzleGenerator::PIECE_HEIGHT,
            'src' => $this->urlGenerator->generate($options['route'], [
                'challenge' => $form->get('challenge')->getData()
            ]),
            'pieces-number' => PuzzleGenerator::PIECES_NUMBER,
            'space-between-pieces' => PuzzleGenerator::SPACE_BETWEEN_PIECES,
            'puzzle-bar' => PuzzleGenerator::PUZZLE_BAR
        ];
        
        parent::buildView($view, $form, $options);
    }
}