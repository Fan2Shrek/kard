<?php

declare(strict_types=1);

namespace App\Game\Service;

use App\Entity\Room;
use App\Game\Model\Event\GameEvent;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;

final class EventPublisher
{
	public function __construct(
		private HubInterface $hub,
		private SerializerInterface $serializer,
	) {
	}

	/**
	 * @param GameEvent[] $events
	 */
	public function publish(Room $room, array $events): void
	{
		$update = new Update(
			\sprintf('game-%s', $room->getId()->toString()),
			$this->serializer->serialize($this->getPayload($events), 'json'),
		);

		$this->hub->publish($update);
	}

	/**
	 * @param GameEvent[] $events
	 *
	 * @return array{events: GameEvent[]}
	 */
	private function getPayload(array $events): array
	{
		return [
			'events' => $events,
		];
	}
}
