<?php

declare(strict_types=1);

namespace App\Api\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\DTO\Play;
use App\Entity\User;
use App\Model\Player;
use App\Service\GameManager\GameManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @todo add tests
 *
 * @implements ProcessorInterface<Play, void>
 */
final class GamePlayProcessor implements ProcessorInterface
{
	public function __construct(
		private GameManager $gameManager,
		private Security $security,
	) {
	}

	public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
	{
		if (!$request = $context['request'] ?? null) {
			throw new \LogicException('Request not found in context');
		}

        $request->attributes->set('_format', 'json');
		$room = $data->getCurrentResource();

		/** @var User $user */
		$user = $this->security->getUser();

        $player = current(array_filter(
            $room->getPlayers(),
            fn (Player $p): bool => $p->id === $user->getId()->toString(),
        ));

        if (false === $player) {
			throw new BadRequestHttpException('Player not found in this room.');
        }

        $this->gameManager->play($room, $player, $data->cards, $data->data);
	}
}
