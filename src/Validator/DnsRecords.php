<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class DnsRecords extends Constraint
{
    public string $message = 'Incorrect or incomplete DNS records';
}
