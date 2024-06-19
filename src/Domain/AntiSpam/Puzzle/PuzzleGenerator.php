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

    public function generate(string $key): Response {
        $positions = $this->challenge->getSolutions($key);
        dump($positions);

        if (!$positions) {
            return new Response('No position found', 404);
        }

        $backgroundPath = $this->chosingAPic();

        $manager = new ImageManager(['driver' => 'gd']);
        $image = $manager->make($backgroundPath);
        $image->fit(PuzzleChallenge::WIDTH, PuzzleChallenge::HEIGHT);

        $pieces = $this->getPieces(PuzzleChallenge::PIECES_NUMBER);

        $holes = [];
        $piecePositions = ['top-right', 'bottom-right', 'top-left'];
        // Randomize the positions of the pieces
        shuffle($positions);
        foreach ($pieces as $index => $piece) {
            $piece = $manager->make($piece);
            $piece->fit(PuzzleChallenge::PIECE_WIDTH, PuzzleChallenge::PIECE_HEIGHT);
            $hole = clone $piece;
            $hole->opacity(80);
            $position = $positions[$index];
            $piecePosition = $piecePositions[$index];

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

        // Randomize the positions of the holes
        // shuffle($positions);
        // shuffle($holes);

        foreach($pieces as $index => $piece) {
            $position = $positions[$index];
            $piecePosition = $piecePositions[$index];
            $hole = $holes[$index];
            $image->insert($piece, $piecePosition)
                ->insert($hole->opacity(80), 'top-left', $position[0] + PuzzleChallenge::PIECE_WIDTH, $position[1]);
        }

        return $image->response('webp');
    }

}