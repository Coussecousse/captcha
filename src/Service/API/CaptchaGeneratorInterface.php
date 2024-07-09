<?php

namespace App\Service\API;

use App\Entity\Puzzle;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Interface CaptchaInterface
 * 
 * Generate the captcha puzzle
 */
interface CaptchaGeneratorInterface
{
    /**
     * Generate the puzzle for the captcha
     * 
     * @return void
     */
    public function generatePuzzle(array $params): JsonResponse;

    /**
     * Verify the answers to the captcha
     * 
     * @param string $key
     * @param array $answers
     * @return bool
     */
    public function verify(string $key, array $answers): bool;


    public function getParams(Puzzle $puzzle): JsonResponse;
}