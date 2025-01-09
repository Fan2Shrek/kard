<?php

namespace App\Command;

use App\Entity\GameMode;
use App\Repository\GameModeRepository;
use App\Service\GameManager\GameMode\GameModeEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:data:init')]
final class InitDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GameModeRepository $gameModeRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = 1;
        foreach (GameModeEnum::cases() as $gm) {
            if ($this->gameModeRepository->findByGameMode($gm)) {
                continue;
            }

            $this->entityManager->persist(new GameMode($gm));
            ++$count;
        }
        $this->entityManager->flush();

        $output->writeln(sprintf('%d game modes added', $count));

        return self::SUCCESS;
    }
}
