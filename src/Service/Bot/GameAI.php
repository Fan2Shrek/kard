<?php

declare(strict_types=1);

namespace App\Service\Bot;

use App\Domain\DTO\GameStateDTO;
use App\Entity\Room;
use App\Game\GameManager;
use App\Game\Model\Card\Hand;
use App\Game\Model\State\GameState;
use App\Game\Model\State\PlayerState;
use Ramsey\Uuid\Uuid;

final class GameAI
{
    private const array ADJECTIVE = [
        'Adorable',
        'Adventurous',
        'Affectionate',
        'Alert',
        'Amusing',
        'Brave',
        'Bright',
        'Charming',
        'Cheerful',
        'Clever',
    ];

    private const array NOUN = [
        'Cat',
        'Dog',
        'Dragon',
        'Unicorn',
        'Phoenix',
        'Fairy',
        'Elf',
        'Gnome',
    ];

    public function __construct(
        private BotClient $botClient,
        private GameManager $gameManager,
    ) {
    }

    public static function create(): PlayerState
    {
        $name = self::ADJECTIVE[array_rand(self::ADJECTIVE)].' '.self::NOUN[array_rand(self::NOUN)];

        return new PlayerState(Uuid::uuid4()->toString(), $name, 0, new Hand([]), true);
    }

    public function playAsBot(Room $room, PlayerState $bot, GameState $state): void
    {
        // the same redacted view the front gets: every hand but the bot's own is
        // null. One redaction to audit instead of a second payload builder here.
        $move = $this->botClient->play(
            $room->getGameMode()->getValue(),
            GameStateDTO::fromState($state, $bot->id),
        );

        $this->gameManager->playAs($room, $bot->id, $move['cards'] ?? [], $move['data'] ?? []);
    }
}
