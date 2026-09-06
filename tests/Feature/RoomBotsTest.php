<?php

use App\Entity\GameMode;
use App\Entity\Room;
use App\Entity\User;
use App\Game\Mode\GameModeEnum;
use App\Service\Bot\GameAI;
use Ramsey\Uuid\Uuid;

covers(Room::class);

test('les bots se retirent dans l ordre inverse de leur ajout', function () {
    $room = new Room(new GameMode(GameModeEnum::PRESIDENT), Uuid::uuid4());

    $first = GameAI::create();
    $second = GameAI::create();
    $room->addBot($first->id, $first);
    $room->addBot($second->id, $second);

    expect($room->removeLastBot())->toBe($second->id);
    expect(array_keys($room->getBots()))->toBe([$first->id]);

    expect($room->removeLastBot())->toBe($first->id);
    expect($room->getBots())->toBe([]);

    // retirer alors qu'il n'y en a plus ne casse rien
    expect($room->removeLastBot())->toBeNull();
});

test('getPlayers() reflete le retrait', function () {
    $room = new Room(new GameMode(GameModeEnum::PRESIDENT), Uuid::uuid4());

    $user = new User('human', 'h@test.com');
    (new ReflectionProperty($user, 'id'))->setValue($user, Uuid::uuid4());
    $room->addParticipant($user);

    $bot = GameAI::create();
    $room->addBot($bot->id, $bot);

    expect($room->getPlayers())->toHaveCount(2);

    $room->removeLastBot();

    $players = $room->getPlayers();
    expect($players)->toHaveCount(1);
    expect($players[0]->isBot)->toBeFalse();
});
