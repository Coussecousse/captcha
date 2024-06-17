<?php

namespace App\Validator;

use App\Domain\AntiSpam\ChallengeInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ChallengeValidator extends ConstraintValidator
{
    public function __construct(private readonly ChallengeInterface $challenge)
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
                $this->context->buildViolation($constraint->message)
                ->addViolation();
                return;
            }
        }

        if (!$this->challenge->verify($value['challenge'], $value['answer'])) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }

    }
}
