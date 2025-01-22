<?php

namespace App\Domain\Command;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler()]
final class SendEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(SendEmailCommand $command): void
    {
        $this->mailer->send($command->email);
    }
}
