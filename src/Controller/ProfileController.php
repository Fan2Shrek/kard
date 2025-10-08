<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\LeaderboardRepository;
use App\Repository\ResultRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(
        LeaderboardRepository $leaderboardRepository,
        ResultRepository $resultRepository): Response
    {
        $user = $this->getUser();
        if (null === $user) {
            return $this->redirectToRoute('app_login');
        }

        /** @var User $user */
        $leaderboard = $leaderboardRepository->findByUser($user);
        $gamesPlayed = $resultRepository->countGamesPlayedByUser($user);
        $gamesWon = $leaderboard?->getWinsNumber() ?? 0;
        $stats = [
            'gamesPlayed' => $gamesPlayed,
            'gamesWon' => $gamesWon,
            'winRate' => $gamesPlayed > 0 ? round(($gamesWon / $gamesPlayed) * 100, 2) : 0,
        ];

        $recentGames = $resultRepository->findRecentGamesByUser($user, 8);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'stats' => $stats,
            'recentGames' => $recentGames,
        ]);
    }
}
