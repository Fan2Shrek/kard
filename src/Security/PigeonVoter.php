<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<'pigeon', string>
 */
final class PigeonVoter extends Voter
{
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return in_array('ROLE_PIGEON', $token->getRoleNames(), true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return 'pigeon' === $attribute;
    }
}
