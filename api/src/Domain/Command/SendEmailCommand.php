<?php

namespace App\Domain\Command;

use Symfony\Component\Mime\Email;

final readonly class SendEmailCommand implements AsyncCommandInterface
{
    public function __construct(
        public Email $email,
    ) {
    }
}
