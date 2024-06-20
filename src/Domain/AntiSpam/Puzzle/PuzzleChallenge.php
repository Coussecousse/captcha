<?php

namespace App\Domain\AntiSpam\Puzzle;

use App\Domain\AntiSpam\CaptchaInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class PuzzleChallenge implements CaptchaInterface
{
    public const WIDTH = 350;
    public const HEIGHT = 200;
    public const PIECE_WIDTH = 50;
    public const PIECE_HEIGHT = 50;
    private const SESSION_KEY = 'puzzles';
    private const PRECISION = 10;
    public const PIECES_NUMBER = 3;
    public const SPACE_BETWEEN_PIECES = 50;

    public function __construct(private readonly RequestStack $requestStack)
    {
        
    }

    public function generateKey(): string
    {
        $session = $this->getSession();
        $now = time() + mt_rand(0, 1000);
        
        $solutions = [];

        $rangesWidth = [];
        $maxHeight = self::HEIGHT - self::PIECE_HEIGHT;
        $lastMinWidth = 0;
        $imageDivision = (self::WIDTH - (self::SPACE_BETWEEN_PIECES * self::PIECES_NUMBER)) / self::PIECES_NUMBER;

        // Calculate the width ranges for the image for each piece
        for ($i = 0; $i < self::PIECES_NUMBER; $i++) {
            $nextWidth = $lastMinWidth + $imageDivision;
            $rangesWidth[] = [$lastMinWidth, $nextWidth];
            $lastMinWidth = $nextWidth + self::SPACE_BETWEEN_PIECES;
        }

        // Generate positions for each piece
        while (count($solutions) < self::PIECES_NUMBER) {
            $piece = 'piece_'.count($solutions) + 1;
            $currentRange = $rangesWidth[count($solutions)];
            $x = mt_rand($currentRange[0], $currentRange[1]);
            $y = mt_rand(0, $maxHeight);
            $newPosition = [$x, $y];
            $solutions[] = ['position' => $newPosition, 'piece' => $piece];
        }
        
        $puzzles = $session->get(self::SESSION_KEY, []);
        $puzzles[] = ['key' => $now, 'solutions' => $solutions];
        $session->set(self::SESSION_KEY, array_slice($puzzles,-10));
        return $now;
    }

    public function verify(string $key, array $answers): bool
    {
        $solutions = $this->getSolutions($key);

        if (!$solutions) return false;

        // Remove puzzle from session to avoid brute force attack
        $session = $this->getSession();
        $puzzles = $session->get(self::SESSION_KEY);
        $session->set(self::SESSION_KEY, array_filter($puzzles, fn(array $puzzle) => $puzzle['key'] != $key));

        $got = $this->stringToPosition($answers);

        foreach($got as $index => $answer) {
            $position = array_filter($solutions, function($item) use ($index) {
                return $item['piece'] == 'piece_'.$index + 1;
            });
            
            if (!empty($position)) {
                $position = reset($position);
                $position = $position['position'];
            }

            $isWithinPrecision = (
                abs($position[0] - $answer[0]) <= self::PRECISION &&
                abs($position[1] - $answer[1]) <= self::PRECISION
            );

        
            if (!$isWithinPrecision) {
                return false;
            }
        }

        return true;
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
    private function stringToPosition(array $answers): array
    {
        $positions = [];
        foreach ($answers as $answer) {
            $parts = explode('-', $answer, 2);
            if (count($parts) !== 2) {
                $positions[] = [-1, -1];
            } else {
                $positions[] = [intval($parts[0]), intval($parts[1])];
            }
        }

        return $positions;
    }
}