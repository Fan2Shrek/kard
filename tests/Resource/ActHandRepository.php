<?php

declare(strict_types=1);

namespace App\Tests\Resource;

use App\Service\Card\HandRepositoryInterface;
use App\Entity\Room;
use App\Entity\User;
use App\Model\Card\Hand;
use App\Tests\AAA\Act\Act;

final class ActHandRepository implements HandRepositoryInterface
{
    public function get(string|User $player, Room $room): ?Hand
    {
        return Act::get('hands')[$player];
    }

    public function getRaw(User $player, Room $room): ?string
    {
        // Implementation for test purposes
        return null;
    }

    public function save(string|User $player, Room $room, Hand $hand): void
    {
        // Implementation for test purposes
    }
}
