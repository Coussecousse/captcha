<?php

namespace App\Controller;

use App\Domain\AntiSpam\ChallengeGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class CaptchaController extends AbstractController 
{
    #[Route('/captcha', name: 'app_captcha')]
    public function captcha(Request $request, ChallengeGenerator $generator): Response 
    {
        return $generator->generate($request->query->get('challenge', ''));
        // return new Response('coucou', 200);
    }
}