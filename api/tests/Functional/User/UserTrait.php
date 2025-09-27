<?php

declare(strict_types=1);

namespace App\Tests\Functional\User;

use App\Entity\User;
use App\Tests\Functional\FunctionalTestCase;

trait UserTrait
{
    protected function createUser(
        string $username = 'default',
        string $email = 'default@gmail.com',
        string $password = 'password',
    ): User {
        \assert($this instanceof FunctionalTestCase);

        $user = new User($username, $email);
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);

        $user->setPassword(self::getContainer()->get('security.password_hasher')
            ->hashPassword($user, $password))
        ;

        $this->getEm()->persist($user);
        $this->getEm()->flush();

        return $user;
    }
}
