<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class TelegramGroup extends Constraint
{
    public string $messageOffline = 'Millaubot is offline';
    public string $messageNotMember= 'Millaubot is not a member of this group';
}
