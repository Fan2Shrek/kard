<?php

namespace App\DataFixtures;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends AbstractFixtures
{
    private const USER_PASSWORD = 'aaa';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function getEntityClass(): string
    {
        return User::class;
    }

    public function getData(): iterable
    {
        yield [
            'username' => 'admin',
            'email' => 'admin@kard.fr',
            'password' => self::USER_PASSWORD,
        ];

        yield [
            'username' => 'user',
            'email' => 'user@gmail.fr',
            'password' => self::USER_PASSWORD,
        ];
    }

    protected function postInstantiate($entity): void
    {
        $entity->setPassword($this->passwordHasher->hashPassword($entity, $entity->getPassword()));
    }
}
