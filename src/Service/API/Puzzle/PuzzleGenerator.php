<?php

namespace App\Service\API\Puzzle;

use App\Entity\Key;
use App\Entity\Position;
use App\Service\API\CaptchaGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class PuzzleGenerator implements CaptchaGeneratorInterface
{
    // public const WIDTH = 350;
    // public const HEIGHT = 200;
    // public const PIECE_WIDTH = 50;
    // public const PIECE_HEIGHT = 50;
    // private const SESSION_KEY = 'puzzles';
    // private const PRECISION = 10;
    // public const PIECES_NUMBER = 3;
    // public const SPACE_BETWEEN_PIECES = 50;
    // public const PUZZLE_BAR = 'top';


    public function __construct (
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        public int $width, 
        public int $height,
        public int $pieceWidth,
        public int $pieceHeight,
        public int $precision,
        public int $piecesNumber,
        public int $spaceBetweenPieces,
        public string $puzzleBar
        )
    { }

    public function setRequestStack(RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Generate random positions for the key
     * 
     * @return Key
     */
    private function generatePositions(Key $key): Key 
    {
        $rangesWidth = [];
        $maxHeight = $this->height - $this->pieceHeight;
        $lastMinWidth = 0;
        $imageDivision = ($this->width- ($this->spaceBetweenPieces * $this->piecesNumber)) / $this->piecesNumber;

        // Calculate the width ranges for the image for each piece
        for ($i = 0; $i < $this->piecesNumber; $i++) {
            $nextWidth = $lastMinWidth + $imageDivision;
            $rangesWidth[] = [$lastMinWidth, $nextWidth];
            $lastMinWidth = $nextWidth + $this->spaceBetweenPieces;
        }

        // Generate positions for each piece
        for ($i = 0; $i < $this->piecesNumber; $i++) {
            $position = new Position();
            $currentRange = $rangesWidth[$i];
            $x = mt_rand($currentRange[0], $currentRange[1]);
            $y = mt_rand(0, $maxHeight);

            $position->setKey($key)
                     ->setX($x)
                     ->setY($y)
                     ->setPosition("$x-$y");
            
            $key->addPosition($position);
        }
        
        return $key;
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

    public function generatePuzzle(): JsonResponse
    {
        // Create a new key entity
        $key = new Key();
        dump($key);

        // Generate positions depending on the parameter number of pieces
        $key = $this->generatePositions($key);

        // Persist the key
        $this->entityManager->persist($key);
        $this->entityManager->flush();

        // Send back JSON response about the puzzle for the form

        return new JsonResponse([
            'key' => $key->getUid(),
            'piecesNumber' => $this->piecesNumber,
            'pieceWidth' => $this->pieceWidth,
            'pieceHeight' => $this->pieceHeight,
            'imageWidth' => $this->width,
            'imageHeight' => $this->height,
            'puzzleBar' => $this->puzzleBar,
            'spaceBetweenPieces' => $this->spaceBetweenPieces,
            'precision' => $this->precision
        ]);
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
            foreach ($puzzles as $index => $challenge) {
                if ($challenge['key'] != $key) continue;
                $challenge['verified'] = 1;
                $this->setSessionPuzzles($challenge);
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
                abs($position[0] - $answer[0]) <= $this->precision &&
                abs($position[1] - $answer[1]) <= $this->precision
            );
            
            if (!$isWithinPrecision) {
                return false;
            }
        }

        // Remove puzzle from session if no error
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