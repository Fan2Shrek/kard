<?php

declare(strict_types=1);

namespace App\Api\Model;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Api\State\Provider\LeaderboardProvider;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/leaderboard',
        provider: LeaderboardProvider::class,
    ),
])]
final class Leaderboard
{
}
