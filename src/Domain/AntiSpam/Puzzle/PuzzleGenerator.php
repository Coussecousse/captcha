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

    public function getPieces(int $number):array 
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
        foreach ($pieces as $index => $piece) {
            $piece = $manager->make($piece);
            $piece->fit(PuzzleChallenge::PIECE_WIDTH, PuzzleChallenge::PIECE_HEIGHT);

            $hole = clone $piece;
            $hole->opacity(80);
            dump(-$positions[$index][0]);
            $piece->insert($image, 'top-left', -$positions[$index][0], -$positions[$index][1])
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
        $piecePositions = ['top-right', 'bottom-right', 'top-left'];
        for ($i = 0; $i < PuzzleChallenge::PIECES_NUMBER; $i++) {
            dump($i);
            $position = $positions[$i];
            $piecePosition = $piecePositions[$i];
            $piece = $pieces[$i];
            $hole = $holes[$i];
            $image->insert($piece, $piecePosition, $position[0], $position[1])
                  ->insert($hole->opacity(80), 'top-left', $position[0], $position[1]);
        }


        return $image->response('webp');
    }

}