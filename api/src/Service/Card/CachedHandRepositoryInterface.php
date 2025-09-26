<?php

declare(strict_types=1);

namespace App\Service\Card;

use App\Entity\Room;

interface CachedHandRepositoryInterface extends HandRepositoryInterface
{
    public function deleteAllHandForRoom(Room $room): void;
}
