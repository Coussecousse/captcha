<?php

namespace App\Domain\AntiSpam;

interface CaptchaInterface
{
    public function generateKey(): string;

    public function verify(string $key, array $answers): bool;

    public function getSolutions(string $key): mixed;
}