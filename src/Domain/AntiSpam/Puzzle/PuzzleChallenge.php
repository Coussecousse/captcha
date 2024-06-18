<?php

namespace App\Domain\AntiSpam\Puzzle;

use App\Domain\AntiSpam\CaptchaInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class PuzzleChallenge implements CaptchaInterface
{
    public const WIDTH = 350;
    public const HEIGHT = 200;
    public const PIECE_WIDTH = 60;
    public const PIECE_HEIGHT = 40;
    private const SESSION_KEY = 'puzzles';
    private const PRECISION = 2;
    public const PIECES_NUMBER = 3;

    public function __construct(private readonly RequestStack $requestStack)
    {
        
    }

    public function generateKey(): string
    {
        $session = $this->getSession();
        $now = time() + mt_rand(0, 1000);
        
        $positions = [];
        while (count($positions) < self::PIECES_NUMBER) {
            $x = mt_rand(0, self::WIDTH - self::PIECE_WIDTH);
            $y = mt_rand(0, self::HEIGHT - self::PIECE_HEIGHT);
            $positions[] = [$x, $y];
        }

        $x = mt_rand(0, self::WIDTH - self::PIECE_WIDTH);
        $y = mt_rand(0, self::HEIGHT - self::PIECE_HEIGHT);
        
        $puzzles = $session->get(self::SESSION_KEY, []);
        $puzzles[] = ['key' => $now, 'solutions' => $positions];
        $session->set(self::SESSION_KEY, array_slice($puzzles,-10));
        return $now;
    }

    public function verify(string $key, string $answer): bool
    {
        $expected = $this->getSolutions($key);

        if (!$expected) return false;

        // Remove puzzle from session to avoid brute force attack
        $session = $this->getSession();
        $puzzles = $session->get(self::SESSION_KEY);
        $session->set(self::SESSION_KEY, array_filter($puzzles, fn(array $puzzle) => $puzzle['key'] != $key));

        $got = $this->stringToPosition($answer);

        return abs($expected[0] - $got[0]) <= self::PRECISION && abs($expected[1] - $got[1]) <= self::PRECISION;
    }

    /**
     * @return int[]|null
     */
    public function getSolutions(string $key): array | null
    {
        $puzzles = $this->getSession()->get(self::SESSION_KEY, []);

        foreach ($puzzles as $puzzle) {
            if ($puzzle['key'] != $key) continue;
            return $puzzle['solutions'];
        }

        return null;
    }

    private function getSession(): Session
    {
        return $this->requestStack->getMainRequest()->getSession();
    }

    /**
     * @return int[]
     */
    private function stringToPosition(string $s): array
    {
        $parts = explode('-', $s, 2);

        if (count($parts) !== 2) {
            return [-1, -1];
        }
        return [intval($parts[0]), intval($parts[1])];
    }
}