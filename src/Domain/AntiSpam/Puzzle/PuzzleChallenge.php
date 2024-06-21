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
    { }

    /**
     * Generate random positions for the pieces
     * 
     * @return array
     */
    private function generatePositions(): array 
    {
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
        
        return $solutions;
    }

    /**
     * Set the puzzle in the session
     * 
     * @param array $puzzle
     * @return void
     */
    private function setSessionPuzzles(array $puzzle): void 
    {
        $session = $this->getSession();

        $puzzles = $session->get(self::SESSION_KEY, []);

        // if the puzzle in the puzzles array already exist 
        // we remove it to avoid duplicates
        $puzzles = array_filter($puzzles, fn(array $p) => $p['key'] != $puzzle['key']);

        $puzzles[] = $puzzle;
        $session->set(self::SESSION_KEY, array_slice($puzzles,-10));
    }

    /**
     * Create the array for the puzzle
     * 
     * @param int $key
     * @param array $solutions
     * @return array
     */
    private function createPuzzle(int $key, array $solutions): array 
    {
        return $puzzle = ['key' => $key, 'solutions' => $solutions];
    }

    public function generatePuzzle(int $key): void
    {
        $solutions = $this->generatePositions();
        
        $puzzle = $this->createPuzzle($key, $solutions);

        $this->setSessionPuzzles($puzzle);
    }

    public function generateKey(): string
    {
        $key = time() + mt_rand(0, 1000);
        
        $this->generatePuzzle($key);

        return $key;
    }

    public function verify(string $key, array $answers): bool
    {
        $puzzle = $this->getPuzzle($key);

        if (!$puzzle) return false;

        // The key is verified a first time
        // To avoid brute force attack, we need to add a field to say that we already verify it
        // Allow to generate new positions when a new image is requested
        if (!array_key_exists('verified', $puzzle)) {
            $puzzles = $this->getSession()->get(self::SESSION_KEY, []);
            foreach ($puzzles as $index => $puzzle) {
                if ($puzzle['key'] != $key) continue;
                $puzzle['verified'] = 1;
                $this->setSessionPuzzles($puzzle);
            } 
        } 

        $got = $this->stringToPosition($answers);

        $solutions = $puzzle['solutions'];

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

        // Remove puzzle from session to avoid brute force attack
        $session = $this->getSession();
        $puzzles = $session->get(self::SESSION_KEY);
        $session->set(self::SESSION_KEY, array_filter($puzzles, fn(array $puzzle) => $puzzle['key'] != $key));

        return true;
    }

    public function getPuzzle(string $key): array | null
    {
        $puzzles = $this->getSession()->get(self::SESSION_KEY, []);
        foreach ($puzzles as $puzzle) {
            if ($puzzle['key'] != $key) continue;

            // If the key is already verified, we need to generate a new puzzle to avoid brute force attack
            if (array_key_exists('verified', $puzzle)) {
                $this->generatePuzzle($key);
            }
            return $puzzle;
        }

        return null;
    }

    private function getSession(): Session
    {
        return $this->requestStack->getMainRequest()->getSession();
    }

    /**
     * Get the string position from the answer(s) and return an array of positions in int
     * 
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