<?php

namespace App\Controller;

use App\Enum\GameStatusEnum;
use App\Repository\ResultRepository;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    use ControllerTrait;

    #[Route('/', name: 'home')]
    public function index(
        RoomRepository $roomRepository,
        UserRepository $userRepository,
        ResultRepository $resultRepository,
    ): Response {
        $playerLeaderboard = [];

        foreach ($userRepository->findAll() as $user) {
            $gamesCount = count(array_filter($roomRepository->findAllRoomWithPlayer($user), fn ($room) => GameStatusEnum::FINISHED === $room->getStatus()));
            $winsCount = count($resultRepository->findBy(['winner' => $user]));
            $playerLeaderboard[] = [
                'player' => $user,
                'gamesCount' => $gamesCount,
                'winsCount' => $winsCount,
                'winRate' => $gamesCount > 0 ? round($winsCount / $gamesCount * 100, 2) : 0,
            ];
        }

        usort($playerLeaderboard, fn ($a, $b) => $b['winRate'] <=> $a['winRate']);
        $playerLeaderboard = array_slice($playerLeaderboard, 0, 10);

        return $this->render('home/index.html.twig', [
            'currentGames' => $roomRepository->findAllCurrent(),
            'playerLeaderboard' => $playerLeaderboard,
        ]);
    }
}
