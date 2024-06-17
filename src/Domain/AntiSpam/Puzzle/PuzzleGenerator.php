<?php

namespace App\Domain\AntiSpam\Puzzle;

use App\Domain\AntiSpam\ChallengeGenerator;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\Response;

class PuzzleGenerator implements ChallengeGenerator
{
    public function __construct(private readonly PuzzleChallenge $challenge) {

    }

    public function generate(string $key): Response {
        $position = $this->challenge->getSolution($key);

        if (!$position) {
            return new Response('No position found', 404);
        }

        [$x, $y] = $position;
        $backgroundPath = sprintf('%s/kitten.webp', __DIR__);
        $piecePath = sprintf('%s/piece.png', __DIR__);
        // dd($piecePath);

        $manager = new ImageManager(['driver' => 'gd']);
        $image = $manager->make($backgroundPath);
        $piece = $manager->make($piecePath);
        $piece->resize(PuzzleChallenge::PIECE_WIDTH, PuzzleChallenge::PIECE_HEIGHT);

        $hole = clone $piece;
        $hole->opacity(60);
        $image
            ->resize(PuzzleChallenge::WIDTH, PuzzleChallenge::HEIGHT);
        $piece->insert($image, 'top-left', -$x, -$y)
              ->mask($hole, true);
        $image
            ->resizeCanvas(
                PuzzleChallenge::PIECE_WIDTH,
                0,
                'left',
                true,
                'rgba(0, 0, 0, 0)'
            )
            ->insert($piece, 'top-right')
            ->insert($hole->opacity(60), 'top-left', $x, $y);

        return $image->response('webp');
        return new Response('Bonjour', 200);

    }

}