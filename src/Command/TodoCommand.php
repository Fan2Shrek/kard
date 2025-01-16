<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:todo')]
final class TodoCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $manyToOneCount = $manyToManyCount = $inheritanceCount = 0;

        $metadataList = $this->entityManager->getMetadataFactory()->getAllMetadata();

        foreach ($metadataList as $metadata) {
            foreach ($metadata->associationMappings as $association) {
                switch ($association['type']) {
                    case ClassMetadata::MANY_TO_ONE:
                        $manyToOneCount++;
                        break;
                    case ClassMetadata::MANY_TO_MANY:
                        $manyToManyCount++;
                        break;
                    default:
                }
            }

            switch ($metadata->inheritanceType) {
                case ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE:
                    $inheritanceCount++;
                    break;
                case ClassMetadata::INHERITANCE_TYPE_JOINED:
                    $inheritanceCount++;
                    break;
                default:
            }
        }

        $output->writeln('Doctrine :');

        $table = new Table($output);
        $table
            ->setHeaders(['Constraints', 'Actual', 'Required', 'Percentage'])
            ->setRows([
                ['Many-to-One', $manyToOneCount, 8, sprintf('%.2f%%', $manyToOneCount / 8 * 100)],
                ['Many-to-Many', $manyToManyCount, 2, sprintf('%.2f%%', $manyToManyCount / 2 * 100)],
                ['Inheritance', $inheritanceCount, 1, sprintf('%.2f%%', $inheritanceCount / 1 * 100)],
                ['Entity', count($metadataList), 10, sprintf('%.2f%%', count($metadataList) / 10 * 100)],
            ])
        ;
        $table->render();

        return self::SUCCESS;
    }
}
