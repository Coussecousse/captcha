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

    public function getParams(): array {
        return [
            'imageWidth' => $this->width,
            'imageHeight' => $this->height,
            'pieceWidth' => $this->pieceWidth,
            'pieceHeight' => $this->pieceHeight,
            'precision' => $this->precision,
            'piecesNumber' => $this->piecesNumber,
            'spaceBetweenPieces' => $this->spaceBetweenPieces,
            'puzzleBar' => $this->puzzleBar
        ];
    }


    public function generatePuzzle(): JsonResponse
    {
        // Create a new key entity
        $key = new Key();

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

    public function verify(string $key, array $answers): bool
    {
        $key = $this->entityManager->getRepository(Key::class)->findOneBy(['uid' => $key]);

        if (!$key) return false;

        $got = $this->stringToPosition($answers);

        $keyPositions = $key->getPositions();

        foreach($got as $index => $answer) {
            $position = $keyPositions[$index];

            $isWithinPrecision = (
                abs($position->getX() - $answer[0]) <= $this->precision &&
                abs($position->getY() - $answer[1]) <= $this->precision
            );
            
            if (!$isWithinPrecision) {

                // Generate new position for the key to avoid brute force attack
                $positions = $key->getPositions(); 
                foreach ($positions as $position) {
                    $this->entityManager->remove($position, true);
                }
                $this->entityManager->flush();

                $key = $this->generatePositions($key);

                $this->entityManager->persist($key);
                $this->entityManager->flush();

                return false;
            }
        }

        // Remove the key from the database
        $this->entityManager->remove($key);
        $this->entityManager->flush();

        // Remove the key from the session
        $session = $this->requestStack->getSession();

        $session->remove('captcha_puzzle');

        return true;
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