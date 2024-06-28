<?php

namespace App\Form\Type;

use App\Service\API\CaptchaGeneratorInterface;
use App\Validator\Captcha;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CaptchaType extends AbstractType
{

    public function __construct(
        private readonly CaptchaGeneratorInterface $challenge, 
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly HttpClientInterface $httpClient) {
        }

    public function configureOptions(OptionsResolver $resolver): void 
    {
        // Create a generateKeyService
        $link = 'http://127.0.0.1:8000/captcha/generatePuzzle';

        $response = $this->httpClient->request('GET', $link); 
        $response = $response->toArray();

        foreach ($response as $key => $value) {
            $options[$key] = $value;
            $resolver->setDefaults([
                $key => $value
            ]);
        }

        $resolver->setDefaults([
            'constraints' => [
                new Captcha()
            ], 
            'error_bubbling' => false,
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
            'data' => $options['key']
        ]);
        for ($i = 1; $i <= $options['piecesNumber']; $i++) {
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
            'width' => $options['imageWidth'],
            'height' => $options['imageHeight'],
            'piece-width' => $options['pieceWidth'],
            'piece-height' => $options['pieceHeight'],
            'src' => $this->urlGenerator->generate($options['route'], [
                'challenge' => $form->get('challenge')->getData()
            ]),
            'pieces-number' => $options['piecesNumber'],
            'puzzle-bar' => $options['puzzleBar']
        ];
        
        parent::buildView($view, $form, $options);
    }
}