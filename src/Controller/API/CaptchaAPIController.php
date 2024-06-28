<?php

namespace App\Controller\API;

use App\Service\API\Puzzle\PuzzleGenerator;
use App\Service\API\Puzzle\PuzzleImageGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CaptchaAPIController extends AbstractController
{
    #[Route('/captcha/api', name: 'app_captcha_api')]
    public function index(Request $request, PuzzleImageGenerator $imageGenerator): Response
    {
        return $imageGenerator->generateImage($request->query->get('challenge', ''));
    }

    #[Route('/captcha/generatePuzzle', name:'app_captcha_api_generate_key')]
    public function generatePuzzle(PuzzleGenerator $puzzleGenerator): JsonResponse  {
        $puzzle = $puzzleGenerator->generatePuzzle();
        
        return $puzzle;
    }
}
