<?php

declare(strict_types=1);

namespace App\Service\Bot;

use App\Entity\Room;
use App\Game\GameManager;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\Hand;
use App\Game\Model\State\GameState;
use App\Game\Model\State\PlayerState;
use App\Game\Model\State\Turn;
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
        $move = $this->botClient->play(
            $room->getGameMode()->getValue(),
            $this->buildPayload($bot, $state),
        );

        $this->gameManager->playAs($room, $bot->id, $move['cards'] ?? [], $move['data'] ?? []);
    }

    /**
     * Only what the bot is allowed to see: its own hand, the public piles and how
     * many cards everyone else holds. Never the whole GameState - that leaks hands.
     *
     * The current round's turns are sent raw so a strategy can derive whatever its
     * game mode derives from them (the trick to beat, a declared rank, a penalty)
     * instead of us guessing per mode here.
     *
     * @return array<string, mixed>
     */
    private function buildPayload(PlayerState $bot, GameState $state): array
    {
        $round = $state->getCurrentRound();
        $turns = array_map(
            fn (Turn $turn): array => [
                'playerId' => $turn->playerId,
                'cardIds' => array_values($turn->cardIds),
                'data' => $turn->data,
            ],
            array_values(null === $round ? [] : $round->turns),
        );

        // hand keys are not guaranteed to be 0..n - array_values() keeps this a JSON
        // array rather than an object, which the strategies iterate positionally
        $hand = array_values($bot->hand->cards);

        // every card the bot may need to reason about: its hand plus the round's
        $known = $hand;

        foreach ($turns as $turn) {
            $known = [...$known, ...$turn['cardIds']];
        }

        $known = array_values(array_unique($known));

        return [
            'hand' => $hand,
            'cards' => array_combine(
                $known,
                array_map(fn (string $id): array => $this->describeCard($state->getCardById($id)), $known),
            ),
            'players' => array_values(array_map(
                fn (PlayerState $p): array => [
                    'id' => $p->id,
                    'cardsCount' => $p->hand->count(),
                    'isBot' => $p->isBot,
                ],
                $state->players,
            )),
            'round' => [
                'isNew' => null === $round || $round->isNew(),
                'turns' => $turns,
            ],
        ];
    }

    /**
     * @return array{id: string, rank: string, suit: string|null}
     */
    private function describeCard(Card $card): array
    {
        return [
            'id' => $card->id,
            'rank' => $card->rank->value,
            'suit' => $card->suit?->value,
        ];
    }
}
