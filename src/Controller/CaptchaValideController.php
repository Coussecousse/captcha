<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CaptchaValideController extends AbstractController
{
    #[Route(path: '/captcha-valide', name: 'app_captcha_valide')]
    public function index(): Response
    {
        return $this->render('captcha/captcha_valide.html.twig', [
        ]);
    }
}
