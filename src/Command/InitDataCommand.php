<?php

namespace App\Command;

use App\Entity\GameMode;
use App\Entity\GameModeDescription;
use App\Repository\GameModeDescriptionRepository;
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
    private int $gameModeCount = 0;
    private int $descriptionCount = 0;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private GameModeRepository $gameModeRepository,
        private GameModeDescriptionRepository $gameModeDescriptionRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->gameModeCount = $this->descriptionCount = 0;
        foreach (GameModeEnum::cases() as $gm) {
            if ($gameMode = $this->gameModeRepository->findByGameMode($gm)) {
                $this->createDescription($gameMode);
                continue;
            }

            $this->entityManager->persist(new GameMode($gm));
            ++$this->gameModeCount;
        }
        $this->entityManager->flush();

        $output->writeln(sprintf('%d game mode(s) added', $this->gameModeCount));
        $output->writeln(sprintf('%d description(s) added', $this->descriptionCount));

        return self::SUCCESS;
    }

    private function createDescription(GameMode $gameMode): GameModeDescription
    {
        if ($description = $this->gameModeDescriptionRepository->findByGameMode($gameMode)) {
            return $description;
        }
        ++$this->descriptionCount;

        return new GameModeDescription($gameMode);
    }
}
