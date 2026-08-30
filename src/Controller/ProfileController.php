<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordForm;
use App\Form\ProfileForm;
use App\Repository\LeaderboardRepository;
use App\Repository\ResultRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
        $user = $this->getConnectedUser();

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

    #[Route('/profile/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        Security $security,
    ): Response {
        $user = $this->getConnectedUser();

        $profileForm = $this->createForm(ProfileForm::class, $user);
        $profileForm->handleRequest($request);

        $passwordForm = $this->createForm(ChangePasswordForm::class);
        $passwordForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Profil mis à jour');

            // le pseudo et le hash du mot de passe font partie de l'identité en session
            return $security->login($user, 'form_login', 'main') ?? $this->redirectToRoute('app_profile');
        }

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $passwordForm->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $entityManager->flush();
            $this->addFlash('success', 'Mot de passe mis à jour');

            return $security->login($user, 'form_login', 'main') ?? $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'profileForm' => $profileForm,
            'passwordForm' => $passwordForm,
        ]);
    }

    #[Route('/profile/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security,
    ): Response {
        $user = $this->getConnectedUser();

        if (!$this->isCsrfTokenValid('delete-account', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $user->anonymize();
        $entityManager->flush();

        $security->logout(false);

        return $this->redirectToRoute('home');
    }

    private function getConnectedUser(): User
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        return $user;
    }
}
