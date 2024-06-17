<?php

namespace App\Controller;

use App\Domain\AntiSpam\CaptchaGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class CaptchaController extends AbstractController 
{
    #[Route('/captcha', name: 'app_captcha')]
    public function captcha(Request $request, CaptchaGenerator $generator): Response 
    {
        return $generator->generate($request->query->get('challenge', ''));
    }
}