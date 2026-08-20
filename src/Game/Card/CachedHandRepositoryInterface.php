<?php

declare(strict_types=1);

namespace App\Game\Card;

use App\Entity\Room;

interface CachedHandRepositoryInterface extends HandRepositoryInterface
{
    public function deleteAllHandForRoom(Room $room): void;
}
