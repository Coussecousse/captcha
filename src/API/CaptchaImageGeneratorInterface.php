<?php

namespace App\API;

use Symfony\Component\HttpFoundation\Response;

interface CaptchaImageGeneratorInterface
{
    public function chosingAPic(): string;
    
    public function getPieces(int $number): array;

    public function generate(string $key): Response;

}