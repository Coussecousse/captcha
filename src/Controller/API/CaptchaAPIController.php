<?php

namespace App\Controller\API;

use App\Repository\KeyRepository;
use App\Service\API\Puzzle\PuzzleGenerator;
use App\Service\API\Puzzle\PuzzleImageGenerator;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CaptchaAPIController extends AbstractController
{
    #[Route('/captcha/api', name: 'app_captcha_api')]
    public function index(Request $request, 
        PuzzleImageGenerator $imageGenerator,
        PuzzleGenerator $puzzleGenerator,
        KeyRepository $keyRepository): Response
    {

        $params = $request->query->all();
        $challenge = $params['challenge'] ?? null;

        if (!$challenge) {
            return new Exception('No challenge provided.');
        }


        $key = $keyRepository->findOneBy(['uid' => $challenge]);

        if (!$key) 
        {
            return new Exception('No challenge found.');
        }

        $params = $puzzleGenerator->getParams();
        
        return $imageGenerator->generateImage($key, $params);
    }

    #[Route('/captcha/generatePuzzle', name:'app_captcha_api_generate_key')]
    public function generatePuzzle(PuzzleGenerator $puzzleGenerator): JsonResponse  {
        $puzzle = $puzzleGenerator->generatePuzzle();

        return $puzzle;
    }
}
