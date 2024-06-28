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
     * Generate a key for the captcha
     * 
     * @return string
     */
    public function generateKey(): string;

    /**
     * Verify the answers to the captcha
     * 
     * @param string $key
     * @param array $answers
     * @return bool
     */
    public function verify(string $key, array $answers): bool;

    /**
     * Get the puzzle from the key
     * 
     * @param string $key
     * @return array|null
     */
    public function getPuzzle(string $key): array | null;

    public function getParams(): array;
}