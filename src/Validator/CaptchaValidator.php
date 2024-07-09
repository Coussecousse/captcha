<?php

namespace App\Validator;

use App\Service\API\CaptchaGeneratorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CaptchaValidator extends ConstraintValidator
{
    public function __construct(
        private readonly CaptchaGeneratorInterface $captcha,
        private readonly HttpClientInterface $httpClient
        )
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

        $params = [
            'key' => $value['key'],
            'answers' => $answers
        ];
        $link = 'http://127.0.0.1:8000/captcha/verify?' . http_build_query($params);

        $response = $this->httpClient->request('GET', $link);
        $response = $response->toArray();

        if (!$response['valid']) {
            $this->context->buildViolation($constraint->invalidCaptcha)
                ->addViolation();
        }

    }
}
