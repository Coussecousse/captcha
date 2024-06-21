<?php

namespace App\Domain\AntiSpam\Puzzle;

use App\Domain\AntiSpam\CaptchaGenerator;
use Exception;
use Intervention\Image\ImageManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class PuzzleGenerator implements CaptchaGenerator
{
    private $appKernel;

    public function __construct(private readonly PuzzleChallenge $challenge, KernelInterface $appKernel) {
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

    public function resizeNecessary($piece, string $value = 'width') {
        $size = 0;
        $positions = [];
        $width = 0;
        $height = 0;
        if ($value == 'width') {
            $size = $piece->width();
            $width = intval((PuzzleChallenge::PIECE_WIDTH - $size) / 2);
            $positions = ['left', 'right'];
        } elseif ($value == 'height') {
            $size = $piece->height();
            $height = intval((PuzzleChallenge::PIECE_HEIGHT - $size) / 2);
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

    public function generate(string $key): Response {
        $solutions = $this->challenge->getSolutions($key);
        
        if (!$solutions) {
            return new Response('No position found', 404);
        }
        
        // The key is already used so we need new positions
        if (array_key_exists('verified', $solutions)) {
            $solutions = $this->challenge->generatePositions();
            $puzzle = $this->challenge->createPuzzle($key, $solutions);
            $this->challenge->setSessionPuzzles($puzzle);
        } else {
            $solutions = $solutions['solutions'];
        }

        $backgroundPath = $this->chosingAPic();

        $manager = new ImageManager(['driver' => 'gd']);
        $image = $manager->make($backgroundPath);
        $image->resize(PuzzleChallenge::WIDTH, PuzzleChallenge::HEIGHT);
        $pieces = $this->getPieces(PuzzleChallenge::PIECES_NUMBER);

        $holes = [];
        
        foreach ($pieces as $index => $piece) {
            $piece = $manager->make($piece);
            $piece->resize(PuzzleChallenge::PIECE_WIDTH, PuzzleChallenge::PIECE_HEIGHT, function ($constraint) {
                $constraint->aspectRatio();
            });
            if ($piece->height() < PuzzleChallenge::PIECE_HEIGHT) {
                $piece = $this->resizeNecessary($piece, 'height');
            } else if ($piece->width() < PuzzleChallenge::PIECE_WIDTH) {
                $piece = $this->resizeNecessary($piece, 'width');
            }
            
            $hole = clone $piece;
            $hole->opacity(80);

            // In case we want to add an halo to the hole
            // $halo = $manager->make($halos[$index]);
            // $halo->fit(PuzzleChallenge::PIECE_WIDTH, PuzzleChallenge::PIECE_HEIGHT);
            // $hole->insert($halo, 'top-left', 0, 0);

            $position = $solutions[$index]['position'];

            // create the piece with the image in it
            $piece->insert($image, 'top-left', -$position[0], -$position[1])
                // and then crop it to the piece size
                  ->mask($hole, true);

            $holes[] = $hole;
            $pieces[$index] = $piece;
        }

        $image
            ->resizeCanvas(
                PuzzleChallenge::PIECE_WIDTH,
                0,
                'left',
                true,
                'rgba(0, 0, 0, 0)'
            )
            ->resizeCanvas(
                PuzzleChallenge::PIECE_WIDTH,
                0,
                'right',
                true,
                'rgba(0, 0, 0, 0)'
            );

        // Easy way
        $piecesPositionsInImages = ['bottom-right', 'top-left', 'top-right'];

        foreach($pieces as $index => $piece) {
            $position = $solutions[$index]['position'];
            $hole = $holes[$index];

            $randomPiecePosition = $piecesPositionsInImages[$index];
            $image
                ->insert($piece, $randomPiecePosition)
                ->insert($hole->opacity(80), 'top-left', $position[0] + PuzzleChallenge::PIECE_WIDTH, $position[1]);
        }
        
        return $image->response('webp');
    }

}