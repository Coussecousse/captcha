<?php

namespace App\Service\API;

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
    public function generatePuzzle(): JsonResponse;

    /**
     * Verify the answers to the captcha
     * 
     * @param string $key
     * @param array $answers
     * @return bool
     */
    public function verify(string $key, array $answers): bool;


    public function getParams(): array;
}