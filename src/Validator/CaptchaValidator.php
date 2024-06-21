<?php

namespace App\Validator;

use App\Domain\AntiSpam\CaptchaInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CaptchaValidator extends ConstraintValidator
{
    public function __construct(private readonly CaptchaInterface $captcha)
    {
        
    }

    /**
     * 
     * @params array{challenge: string, answer: string} $value
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        // Check if the value isn't blank
        foreach($value as $val) {
            if (null === $val || '' === $val) {
                $this->context->buildViolation($constraint->emptyResponse)
                ->addViolation();
                return;
            }
        }

        foreach($value as $key => $val) {
            if (str_starts_with($key, 'answer_')) {
                $answers[] = $val;
            }
        }

        if (!$this->captcha->verify($value['challenge'], $answers)) {
            $this->context->buildViolation($constraint->invalidCaptcha)
                ->addViolation();
        }

    }
}
