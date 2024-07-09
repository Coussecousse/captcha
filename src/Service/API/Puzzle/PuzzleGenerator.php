<?php

namespace App\Service\API\Puzzle;

use App\Entity\Key;
use App\Entity\Position;
use App\Entity\Puzzle;
use App\Service\API\CaptchaGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class PuzzleGenerator implements CaptchaGeneratorInterface
{
    private int $width;
    private int $height;
    private int $pieceWidth;
    private int $pieceHeight;
    private int $precision;
    private int $piecesNumber;
    private int $spaceBetweenPieces;
    private string $puzzleBar;

    public function __construct (
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager
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
        $puzzle = $key->getPuzzle();
        $rangesWidth = [];
        $maxHeight = $puzzle->getHeight() - $puzzle->getPieceHeight();
        $lastMinWidth = 0;
        $imageDivision = ($puzzle->getWidth()- ($puzzle->getSpaceBetweenPieces() * $puzzle->getPiecesNumber())) / $puzzle->getPiecesNumber();

        // Calculate the width ranges for the image for each piece
        for ($i = 0; $i < $puzzle->getPiecesNumber(); $i++) {
            $nextWidth = $lastMinWidth + $imageDivision;
            $rangesWidth[] = [$lastMinWidth, $nextWidth];
            $lastMinWidth = $nextWidth + $puzzle->getSpaceBetweenPieces();
        }

        // Generate positions for each piece
        for ($i = 0; $i < $puzzle->getPiecesNumber(); $i++) {
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

    private function setParams(array $params): void {
        $this->width = $params['imageWidth'];
        $this->height = $params['imageHeight'];
        $this->pieceWidth = $params['pieceWidth'];
        $this->pieceHeight = $params['pieceHeight'];
        $this->precision = $params['precision'];
        $this->piecesNumber = $params['piecesNumber'];
        $this->spaceBetweenPieces = $params['spaceBetweenPieces'];
        $this->puzzleBar = $params['puzzleBar'];
    }

    public function getParams(Puzzle $puzzle): JsonResponse {
        return new JsonResponse([
            'imageWidth' => $puzzle->getWidth(),
            'imageHeight' => $puzzle->getHeight(),
            'pieceWidth' => $puzzle->getPieceWidth(),
            'pieceHeight' => $puzzle->getPieceHeight(),
            'precision' => $puzzle->getPrecision(),
            'piecesNumber' => $puzzle->getPiecesNumber(),
            'spaceBetweenPieces' => $puzzle->getSpaceBetweenPieces(),
            'puzzleBar' => $puzzle->getPuzzleBar()
        ]);
    }

    public function generatePuzzle(array $params): JsonResponse
    {
        // Set params of the puzzle
        $this->setParams($params);

        // Create a new key entity
        $key = new Key();

        // Create puzzle attached to key with params
        $puzzle = new Puzzle();
        $puzzle ->setWidth($params['imageWidth'])
                ->setHeight($params['imageHeight'])
                ->setPieceWidth($params['pieceWidth'])
                ->setPieceHeight($params['pieceHeight'])
                ->setPrecision($params['precision'])
                ->setPiecesNumber($params['piecesNumber'])
                ->setSpaceBetweenPieces($params['spaceBetweenPieces'])
                ->setPuzzleBar($params['puzzleBar'])
                ->setKey($key);

        $key->setPuzzle($puzzle);

        // Generate positions depending on the parameter number of pieces
        $key = $this->generatePositions($key);

        // Persist the key
        $this->entityManager->persist($key);
        $this->entityManager->flush();

        // Send back JSON response about the puzzle for the form
        return new JsonResponse([
            'key' => $key->getUid(),
            'piecesNumber' => $params['piecesNumber'],
            'pieceWidth' =>$params['pieceWidth'],
            'pieceHeight' => $params['pieceHeight'],
            'imageWidth' => $params['imageWidth'],
            'imageHeight' => $params['imageHeight'],
            'puzzleBar' => $params['puzzleBar'],
            'spaceBetweenPieces' => $params['spaceBetweenPieces'],
            'precision' => $params['precision']
        ]);
    }

    public function verify(string $key, array $answers): JsonResponse
    {
        $key = $this->entityManager->getRepository(Key::class)->findOneBy(['uid' => $key]);
        $puzzle = $key->getPuzzle();

        if (!$key) return false;

        $got = $this->stringToPosition($answers);

        $keyPositions = $key->getPositions();

        foreach($got as $index => $answer) {
            $position = $keyPositions[$index];

            $isWithinPrecision = (
                abs($position->getX() - $answer[0]) <= $puzzle->getPrecision() &&
                abs($position->getY() - $answer[1]) <= $puzzle->getPrecision()
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

                return new JsonResponse(['valid' => false]);
            }
        }

        // Remove the key from the database
        $this->entityManager->remove($key);
        $this->entityManager->flush();

        // Remove the key from the session
        $session = $this->requestStack->getSession();

        $session->remove('captcha_puzzle');

        return new JsonResponse(['valid' => true]);
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