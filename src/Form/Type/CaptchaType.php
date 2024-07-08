<?php

namespace App\Form\Type;

use App\Service\API\CaptchaGeneratorInterface;
use App\Validator\Captcha;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CaptchaType extends AbstractType
{

    public function __construct(
        private readonly CaptchaGeneratorInterface $challenge, 
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
        private readonly HttpClientInterface $httpClient) {
    }

    public function configureOptions(OptionsResolver $resolver): void 
    {
        // Set the key in the session to avoid new key regeneration
        $session = $this->requestStack->getSession();

        if (!$session->has('captcha_puzzle') || $this->requestStack->getCurrentRequest()->getMethod() === 'GET') {
            
            // Create a generateKeyService
            $link = 'http://127.0.0.1:8000/captcha/generatePuzzle';
            
            $response = $this->httpClient->request('GET', $link); 
            $response = $response->toArray();

            $puzzleSession = [
                'key' => $response['key'],
            ];
            
            $session->set('captcha_puzzle', $puzzleSession); 
        } else {
            // Should get the puzzle without generate a new one
            $link = 'http://127.0.0.1:8000/captcha/getParams';

            $params = $this->httpClient->request('GET', $link);
            $params = $params->toArray();

            $key = $session->get('captcha_puzzle')['key'];

            $response = [...$params, 'key' => $key];
        }
        
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
        $builder->add('key', HiddenType::class, [
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
                ],
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
                'key' => $form->get('key')->getData()
            ]),
            'pieces-number' => $options['piecesNumber'],
            'puzzle-bar' => $options['puzzleBar']
        ];
        
        parent::buildView($view, $form, $options);
    }
}