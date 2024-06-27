<?php

namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CaptchaAPIController extends AbstractController
{
    #[Route('/captcha/api', name: 'app_captcha_api')]
    public function index(): Response
    {
        return $this->render('captcha_api/index.html.twig', [
            'controller_name' => 'CaptchaAPIController',
        ]);
    }
}
