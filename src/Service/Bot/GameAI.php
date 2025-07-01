<?php

declare(strict_types=1);

namespace App\Service\Bot;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Model\Card\Card;
use App\Model\Card\Hand;
use App\Model\GameContext;
use App\Model\Player;
use App\Service\GameManager\GameManager;
use Symfony\Component\Serializer\SerializerInterface;

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
        'Charming',
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
        private SerializerInterface $serializer,
        private GameManager $gameManager,
    ) {
    }

    public static function create(): Player
    {
        $name = self::ADJECTIVE[array_rand(self::ADJECTIVE)].' '.self::NOUN[array_rand(self::NOUN)];

        return new Player(
            strtolower($name),
            username: $name,
            cardsCount: 0,
            isBot: true,
        );
    }

    public function playAsBot(Player $bot, GameContext $ctx, Hand $hand): void
    {
        $move = $this->botClient->play([
            'ctx' => $this->serializer->serialize($ctx, 'json'),
            'hand' => $this->serializer->serialize($hand, 'json'),
        ]);

        $cards = array_map(fn ($card): Card => new Card(Suit::from($card['suit']), Rank::from($card['rank'])), $move['cards'] ?? []);

        $this->gameManager->play($ctx->getRoom(), $bot, $cards, $move['data'] ?? []);
    }
}
