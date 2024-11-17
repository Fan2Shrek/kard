<?php

namespace App\Service;

use App\Entity\Room;
use App\Model\Card\Card;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Contracts\Cache\CacheInterface;

final class GamePlayer
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly CacheInterface $cache,
    ) {
    }

    public function play(Card $card, Room $room): void
    {
        $this->hub->publish(new Update(
            $room->getMercureTopic(),
            json_encode([
                'type' => 'card_played',
                'card' => $card,
            ])
        ));
    }
}
