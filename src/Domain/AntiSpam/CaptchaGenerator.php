<?php

namespace App\Domain\AntiSpam;

use Symfony\Component\HttpFoundation\Response;

interface CaptchaGenerator
{
    public function chosingAPic(): string;
    
    public function getPieces(): array;

    public function generate(string $key): Response;

}