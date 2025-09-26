<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;

trait ControllerTrait
{
    protected function getUser(): User
    {
        $user = parent::getUser();

        if (!$user instanceof User) {
            throw new \LogicException('User must be an instance of User');
        }

        return $user;
    }
}
