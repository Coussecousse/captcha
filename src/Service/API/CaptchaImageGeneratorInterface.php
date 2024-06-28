<?php

namespace App\Service\API;

use Symfony\Component\HttpFoundation\Response;

interface CaptchaImageGeneratorInterface
{
    public function chosingAPic(): string;
    
    public function getPieces(int $number): array;

    public function generateImage(string $key): Response;

}