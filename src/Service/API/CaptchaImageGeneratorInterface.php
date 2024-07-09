<?php

namespace App\Service\API;

use App\Entity\Key;
use Symfony\Component\HttpFoundation\Response;

interface CaptchaImageGeneratorInterface
{
    public function chosingAPic(): string;
    
    public function getPieces(int $number): array;

    public function generateImage(Key $key): Response;
}