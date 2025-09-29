<?php

declare(strict_types=1);

namespace App\Api\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Enum\GameStatusEnum;
use App\Repository\ResultRepository;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;

final class LeaderboardProvider implements ProviderInterface
{
	public function __construct(
		private UserRepository $userRepository,
		private RoomRepository $roomRepository,
		private ResultRepository $resultRepository,
	) {
	}

	public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
	{
		$leaderboard = [];

        foreach ($this->userRepository->findAll() as $user) {
            $gamesCount = count(array_filter($this->roomRepository->findAllRoomWithPlayer($user), fn ($room) => GameStatusEnum::FINISHED === $room->getStatus()));
            $winsCount = count($this->resultRepository->findBy(['winner' => $user]));
			$leaderboard[] = [
				'player' => $user,
				'gamesCount' => $gamesCount,
				'winsCount' => $winsCount,
				'winRate' => $gamesCount > 0 ? round($winsCount / $gamesCount * 100, 2) : 0,
			];
        }

        usort($leaderboard, fn ($a, $b) => $b['winsCount'] <=> $a['winsCount']);

        return array_slice($leaderboard, 0, 10);
	}
}
