<?php

namespace App\Validator;

use App\Service\SendGridService;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DnsRecordsValidator extends ConstraintValidator
{
    private SendGridService $service;

    public function __construct(SendGridService $service)
    {
        $this->service = $service;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof DnsRecords) {
            throw new UnexpectedTypeException($constraint, DnsRecords::class);
        }

        $tld = null;
        $group = null;
        foreach ($value as $item) {
            if ('mx' === $item['type']) {
                $tld = $item['host'];
            }
            if ('txt' === $item['type']) {
                $group = $item['data'];
            }
        }
        if (!$this->service->isDomainValid($tld, $group)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
