<?php

namespace App\Validator;

use App\Service\TelegramService;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class TelegramGroupValidator extends ConstraintValidator
{
    private TelegramService $service;

    public function __construct(TelegramService $service)
    {
        $this->service = $service;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof TelegramGroup) {
            throw new UnexpectedTypeException($constraint, TelegramGroup::class);
        }

        if (!$this->service->isOnline()) {
            $this->context->buildViolation($constraint->messageOffline)->addViolation();
        }
        if (!$this->service->isMember($value)) {
            $this->context->buildViolation($constraint->messageNotMember)->addViolation();
        }
    }
}
