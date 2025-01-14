<?php

namespace App\Command;

use App\Entity\Leaderboard;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCronTask('0 0 * * *')] // Run every day at midnight
#[AsCommand(
    name: 'app:generate-leaderboard',
    description: 'Generate a leaderboard of the previous day',
)]
final class GenerateLeaderboardCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $data = $this->userRepository->findYesterdayBestPlayer();
        if (null === $data) {
            $style->warning('No data found.');

            return self::SUCCESS;
        }

        $leaderboard = (new Leaderboard())
            ->setPlayer($data['user'])
            ->setWinsNumber($data['wins']);

        $this->entityManager->persist($leaderboard);
        $this->entityManager->flush();

        $style->success('Leaderboard generated successfully.');

        return self::SUCCESS;
    }
}
