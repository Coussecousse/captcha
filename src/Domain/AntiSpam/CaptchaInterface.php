<?php

namespace App\Domain\AntiSpam;

/**
 * Interface CaptchaInterface
 * 
 * Generate the captcha puzzle
 */
interface CaptchaInterface
{
    /**
     * Generate the puzzle for the captcha
     * 
     * @param int $key
     * @return void
     */
    public function generatePuzzle(int $key): void;

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
}