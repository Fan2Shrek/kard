<?php

declare(strict_types=1);

namespace App\Game\Card;

use App\Entity\Room;
use App\Entity\User;
use App\Game\Model\Card\Hand;

interface HandRepositoryInterface
{
    public function get(string|User $player, Room $room): ?Hand;

    public function getRaw(User $player, Room $room): ?string;

    public function save(string|User $player, Room $room, Hand $hand): void;
}
