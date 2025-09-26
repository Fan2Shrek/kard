<?php

declare(strict_types=1);

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Core\User\UserInterface;

final class JWTAuthenticationSuccessHandler
{
    private JWTTokenManagerInterface $jwtManager;

    public function __construct(JWTTokenManagerInterface $jwtManager)
    {
        $this->jwtManager = $jwtManager;
    }

    public function __invoke(UserInterface $user, string $firewallName, array $data): JsonResponse
    {
        $token = $this->jwtManager->create($user);

        $response = new JsonResponse(['success' => true]);

        $response->headers->setCookie(
            Cookie::create('auth_token', $token, time() + 3600, '/', null, true, true, false, 'Strict')
        );

        return $response;
    }
}
