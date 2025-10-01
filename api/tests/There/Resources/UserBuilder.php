<?php

declare(strict_types=1);

namespace App\Tests\There\Resources;

use App\Entity\User;

/**
 * @extends AbstractBuilder<User>
 */
final class UserBuilder extends AbstractBuilder
{
    public const string DEFAULT_PASSWORD = 'password';

    private ?string $username = null;

    public function __construct($container)
    {
        parent::__construct($container, User::class);
    }

    public function withUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    protected function getParams(): array
    {
        return [
            'username' => $this->username ?? 'user_'.uniqid(),
            'email' => 'user_'.uniqid().'@there.test',
        ];
    }

    protected function afterBuild(object $entity): void
    {
        $entity->setPassword($this->container->get('security.password_hasher')->hashPassword($entity, self::DEFAULT_PASSWORD));
    }
}
