<?php

namespace App\Domain\AntiSpam;

use Symfony\Component\HttpFoundation\Response;

interface CaptchaGenerator
{
    public function generate(string $key): Response;

}