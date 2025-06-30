<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Card\Hand;
use App\Model\GameContext;
use App\Model\Player;

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
        dd('Bot is playing', [
            'bot' => $bot,
            'ctx' => $ctx,
            'hand' => $hand,
        ]);
    }
}
