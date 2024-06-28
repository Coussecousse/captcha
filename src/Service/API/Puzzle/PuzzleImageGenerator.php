<?php

namespace App\Service\API\Puzzle;

use App\Entity\Key;
use App\Service\API\CaptchaImageGeneratorInterface;
use Exception;
use Intervention\Image\ImageManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class PuzzleImageGenerator implements CaptchaImageGeneratorInterface
{
    private $appKernel;

    public function __construct(private readonly PuzzleGenerator $puzzle, KernelInterface $appKernel) {
        $this->appKernel = $appKernel;
    }

    public function chosingAPic(): string {

        $imagesFormat = ['jpg', 'jpeg', 'png', 'webp'];
        
        $images = [];
                
        $project_dir = $this->appKernel->getProjectDir();
        $directory = $project_dir.'/assets/captcha/images';

        foreach($imagesFormat as $format) {
            $pattern = $directory . '/*.' . $format;
            $images = array_merge($images, glob($pattern));
        }

        if (empty($images)) {
            return new Exception('No images found in the directory.');
        }

        $randomImage = $images[array_rand($images)];

        return $randomImage;
    }

    public function getPieces(int $number): array 
    {
        $pieces = [];
        $project_dir = $this->appKernel->getProjectDir();

        $directory = $project_dir.'/assets/captcha/pieces';

        $finder = new Finder();
        $finder->files()->in($directory)->notName('*_halo*');

        if ($finder->hasResults()) {
            foreach($finder as $file) {
                if (count($pieces) == $number) break; 
                $pieces[] = $file->getRealPath();
            }
        }

        return $pieces;
    }

    public function resizeNecessary($piece, string $value = 'width', $params) {
        $size = 0;
        $positions = [];
        $width = 0;
        $height = 0;
        if ($value == 'width') {
            $size = $piece->width();
            $width = intval(($params['pieceWidth'] - $size) / 2);
            $positions = ['left', 'right'];
        } elseif ($value == 'height') {
            $size = $piece->height();
            $height = intval(($params['pieceHeight'] - $size) / 2);
            $positions = ['top', 'bottom'];
        }

        foreach ($positions as $position) {
            $piece->resizeCanvas(
                $width,
                $height,
                $position, 
                true, 
                'rgba(0, 0, 0, 0)'
            );
        }

        return $piece;
    } 

    public function generateImage(Key $key, array $params): Response {
        $positions = $key->getPositions();

        if ($positions->isEmpty()) {
            return new Response('No position found', 404);
        }

        // TODO
        // Add column verified to the key to generate new positions if needed


        $backgroundPath = $this->chosingAPic();
        
        // Make the image
        $manager = new ImageManager(['driver' => 'gd']);
        $image = $manager->make($backgroundPath);
        dump($params);
        $image->resize($params['width'], $params['height']);
        $pieces = $this->getPieces($params['piecesNumber']);

        $holes = [];


        foreach ($pieces as $index => $piece) {
            $piece = $manager->make($piece);
            $piece->resize($params['pieceWidth'], $params['pieceHeight'], function ($constraint) {
                $constraint->aspectRatio();
            });
            if ($piece->height() < $params['height']) {
                $piece = $this->resizeNecessary($piece, 'height', $params);
            } else if ($piece->width() < $params['width']) {
                $piece = $this->resizeNecessary($piece, 'width', $params);
            }
            
            $hole = clone $piece;
            $hole->opacity(80);

            // In case we want to add an halo to the hole
            // $halo = $manager->make($halos[$index]);
            // $halo->fit(PuzzleChallenge::PIECE_WIDTH, PuzzleChallenge::PIECE_HEIGHT);
            // $hole->insert($halo, 'top-left', 0, 0);

            $position = $positions[$index];

            // create the piece with the image in it
            $piece->insert($image, 'top-left', -$position->getX(), -$position->getY())
                // and then crop it to the piece size
                  ->mask($hole, true);

            $holes[] = $hole;
            $pieces[$index] = $piece;
        }

        $image
            ->resizeCanvas(
                $params['pieceWidth'],
                0,
                'left',
                true,
                'rgba(0, 0, 0, 0)'
            )
            ->resizeCanvas(
                $params['pieceWidth'],
                0,
                'right',
                true,
                'rgba(0, 0, 0, 0)'
            );

        // Generate the positions for the pieces
        // Easy way
        $piecesPositionsInImages = ['bottom-right', 'top-left', 'top-right'];

        foreach($pieces as $index => $piece) {
            $position = $positions[$index];
            $hole = $holes[$index];

            $randomPiecePosition = $piecesPositionsInImages[$index];
            $image
                ->insert($piece, $randomPiecePosition)
                ->insert($hole->opacity(80), 'top-left', $position->getX() + $params['pieceWidth'], $position->getY());
        }
        
        return $image->response('webp');
    }

    // public function generateImageBis(string $key): Response {
    //     $puzzle = $this->puzzle->getPuzzle($key);
    //     $solutions = $puzzle['solutions'];

    //     if (!$puzzle) {
    //         return new Response('No position found', 404);
    //     }

    //     // If the key is already verified, we need to generate a new puzzle to avoid brute force attack
    //     if (array_key_exists('verified', $puzzle)) {
    //         $this->puzzle->generatePuzzle($key);
    //         $puzzle = $this->puzzle->getPuzzle($key);
    //         $solutions = $puzzle['solutions'];
    //     }

    //     $backgroundPath = $this->chosingAPic();

    //     $manager = new ImageManager(['driver' => 'gd']);
    //     $image = $manager->make($backgroundPath);
    //     $image->resize(PuzzleGenerator::WIDTH, PuzzleGenerator::HEIGHT);
    //     $pieces = $this->getPieces(PuzzleGenerator::PIECES_NUMBER);

    //     $holes = [];
        
    //     foreach ($pieces as $index => $piece) {
    //         $piece = $manager->make($piece);
    //         $piece->resize(PuzzleGenerator::PIECE_WIDTH, PuzzleGenerator::PIECE_HEIGHT, function ($constraint) {
    //             $constraint->aspectRatio();
    //         });
    //         if ($piece->height() < PuzzleGenerator::PIECE_HEIGHT) {
    //             $piece = $this->resizeNecessary($piece, 'height');
    //         } else if ($piece->width() < PuzzleGenerator::PIECE_WIDTH) {
    //             $piece = $this->resizeNecessary($piece, 'width');
    //         }
            
    //         $hole = clone $piece;
    //         $hole->opacity(80);

    //         // In case we want to add an halo to the hole
    //         // $halo = $manager->make($halos[$index]);
    //         // $halo->fit(PuzzleChallenge::PIECE_WIDTH, PuzzleChallenge::PIECE_HEIGHT);
    //         // $hole->insert($halo, 'top-left', 0, 0);

    //         $position = $solutions[$index]['position'];

    //         // create the piece with the image in it
    //         $piece->insert($image, 'top-left', -$position[0], -$position[1])
    //             // and then crop it to the piece size
    //               ->mask($hole, true);

    //         $holes[] = $hole;
    //         $pieces[$index] = $piece;
    //     }

    //     // $image
    //     //     ->resizeCanvas(
    //     //         $params['pieceWidth'],
    //     //         0,
    //     //         'left',
    //     //         true,
    //     //         'rgba(0, 0, 0, 0)'
    //     //     )
    //     //     ->resizeCanvas(
    //     //         $params['pieceWidth'],
    //     //         0,
    //     //         'right',
    //     //         true,
    //     //         'rgba(0, 0, 0, 0)'
    //     //     );

    //     // Generate the positions for the pieces
    //     // Easy way
    //     $piecesPositionsInImages = ['bottom-right', 'top-left', 'top-right'];

    //     foreach($pieces as $index => $piece) {
    //         $position = $solutions[$index]['position'];
    //         $hole = $holes[$index];

    //         $randomPiecePosition = $piecesPositionsInImages[$index];
    //         $image
    //             ->insert($piece, $randomPiecePosition)
    //             ->insert($hole->opacity(80), 'top-left', $position[0] + PuzzleGenerator::PIECE_WIDTH, $position[1]);
    //     }
        
    //     return $image->response('webp');
    // }

}