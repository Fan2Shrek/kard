<?php

namespace App\Command;

use App\Domain\Command\SendEmailCommand;
use App\Entity\Leaderboard;
use App\Repository\ResultRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;
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
        private ResultRepository $resultRepository,
        private UserRepository $userRepository,
        /* private MessageBusInterface $messageBus, */
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $data = $this->resultRepository->findYesterdayBestPlayer();
        if (null === $data) {
            $style->warning('No data found.');

            return self::SUCCESS;
        }

        $user = $this->userRepository->find($data['user']);

        $leaderboard = new Leaderboard(player: $user, winsNumber: $data['wins']);

        $this->entityManager->persist($leaderboard);
        $this->entityManager->flush();

        $email = new Email();

        $email
            ->from('oui@kard.fr')
            ->to($user->getEmail())
            ->subject('Congratulations!')
            ->text('You are the best player of the day!')
        ;

        /* $this->messageBus->dispatch(new SendEmailCommand($email)); */

        $style->success('Leaderboard generated successfully.');

        return self::SUCCESS;
    }
}
