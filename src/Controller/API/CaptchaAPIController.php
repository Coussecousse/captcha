<?php

namespace App\Controller\API;

use App\Repository\KeyRepository;
use App\Repository\PuzzleRepository;
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
        KeyRepository $keyRepository): Response
    {

        $params = $request->query->all();
        $challenge = $params['key'] ?? null;

        if (!$challenge) {
            return new Exception('No challenge provided.');
        }


        $key = $keyRepository->findOneBy(['uid' => $challenge]);

        if (!$key) 
        {
            return new Exception('No challenge found.');
        }

        return $imageGenerator->generateImage($key);
    }

    #[Route('/captcha/generatePuzzle', name:'app_captcha_api_generate_key')]
    public function generatePuzzle(PuzzleGenerator $puzzleGenerator, Request $request): JsonResponse  {
        $params = $request->query->all();
        $puzzle = $puzzleGenerator->generatePuzzle($params);

        return $puzzle;
    }
   
    #[Route('/captcha/getPuzzle', name:'app_captcha_api_get_puzzle')]
    public function getPuzzle(Request $request, 
        KeyRepository $keyRepository,
        PuzzleRepository $puzzleRepository,
        PuzzleGenerator $puzzleGenerator): JsonResponse {
        $uid = $request->query->get('key');
        $key = $keyRepository->findOneBy(['uid' => $uid]);
        $puzzle = $puzzleRepository->findOneBy(['key' => $key]);

        if (!$puzzle) {
            return new Exception('No puzzle found.');
        }

        return $puzzleGenerator->getParams($puzzle);
    }
}
