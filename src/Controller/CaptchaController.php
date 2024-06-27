<?php

namespace App\Controller;

use App\API\Puzzle\PuzzleImageGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class CaptchaController extends AbstractController 
{
    #[Route('/captcha', name: 'app_captcha')]
    public function captcha(Request $request, PuzzleImageGenerator $generator): Response 
    {
        return $generator->generate($request->query->get('challenge', ''));
    }
}