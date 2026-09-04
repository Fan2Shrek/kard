<?php

use App\Entity\GameMode;
use App\Enum\GameEventTypeEnum;
use App\Game\Mode\GameModeEnum;
use App\Game\Mode\MenteurGameMode;
use App\Tests\AAA\Act\Act;
use App\Tests\AAA\Arrange\Arrange;

covers(MenteurGameMode::class);

beforeEach(function () {
    Act::reset();
    Act::addContext('gamePlayer', new MenteurGameMode());
    Act::addContext('gameMode', new GameMode(GameModeEnum::MENTEUR));
});

pest()->group('Menteur');

describe('Menteur: annonce de rang', function () {
    test('Le premier rang d\'un round est libre', function () {
        Arrange::setGameStarted();

        Act::playCard(7, 's', ['rank' => '7']);
    })->throwsNoExceptions();

    test('Le rang suivant dans le cycle est accepté', function () {
        Arrange::setMenteurRound([
            ['cards' => [7], 'rank' => 7],
        ]);

        Act::playCard(9, 's', ['rank' => '8']);
    })->throwsNoExceptions();

    test('Un rang qui ne suit pas le cycle est rejeté', function () {
        Arrange::setMenteurRound([
            ['cards' => [7], 'rank' => 7],
        ]);

        Act::playCard(9, 's', ['rank' => '9']);
    })->throws('rank.sequence.invalid');

    test('Le cycle boucle après l\'as (1) vers le 2', function () {
        Arrange::setMenteurRound([
            ['cards' => [1], 'rank' => 1],
        ]);

        Act::playCard(2, 's', ['rank' => '2']);
    })->throwsNoExceptions();

    test('Le cycle boucle après le 2 vers le 3', function () {
        Arrange::setMenteurRound([
            ['cards' => [2], 'rank' => 2],
        ]);

        Act::playCard(3, 's', ['rank' => '3']);
    })->throwsNoExceptions();

    test('Il faut annoncer au moins une carte', function () {
        Arrange::setGameStarted();

        Act::playCard(null, 's', ['rank' => '7']);
    })->throws('turn.at_least_one_card');

    test('Le rang doit être précisé', function () {
        Arrange::setGameStarted();

        Act::playCard(7, 's');
    })->throws('rank.not_set');

    test('Le rang doit être valide', function () {
        Arrange::setGameStarted();

        Act::playCard(7, 's', ['rank' => 'not-a-rank']);
    })->throws('rank.invalid');
});

describe('Menteur: contestation', function () {
    test('On ne peut pas contester en tout début de round', function () {
        Arrange::setGameStarted();

        Act::playCard(null, 's', ['challenge' => true]);
    })->throws('challenge.nothing_to_challenge');

    test('Une contestation ne prend pas de cartes', function () {
        Arrange::setMenteurRound([
            ['cards' => [7], 'rank' => 7],
        ]);

        Act::playCards([[8, 's']], ['challenge' => true]);
    })->throws('challenge.no_cards');

    test('Un mensonge démasqué donne le tas au menteur', function () {
        Arrange::setMenteurRound([
            ['cards' => [7], 'rank' => 8], // played a 7, declared an 8 - a lie
        ]);

        Act::playCard(null, 's', ['challenge' => true]);

        expect(Act::get('gameContext')->getPlayerStateById('player2-id')->hand->count())->toBe(1);
    });

    test('Le tour revient au gagnant (l\'accusateur) après un mensonge démasqué', function () {
        Arrange::setMenteurRound([
            ['cards' => [7], 'rank' => 8],
        ]);

        Act::playCard(null, 's', ['challenge' => true]);

        expect(Act::get('gameContext')->currentPlayerId)->toBe('player-id');
    });

    test('Une fausse accusation donne le tas à l\'accusateur', function () {
        Arrange::setMenteurRound([
            ['cards' => [8], 'rank' => 8], // played an 8, declared an 8 - the truth
        ]);

        Act::playCard(null, 's', ['challenge' => true]);

        expect(Act::get('gameContext')->getPlayerStateById('player-id')->hand->count())->toBe(1);
    });

    test('Le tour revient au gagnant (le déclarant) après une fausse accusation', function () {
        Arrange::setMenteurRound([
            ['cards' => [8], 'rank' => 8],
        ]);

        Act::playCard(null, 's', ['challenge' => true]);

        expect(Act::get('gameContext')->currentPlayerId)->toBe('player2-id');
    });

    test('Une contestation résolue vide le round courant', function () {
        Arrange::setMenteurRound([
            ['cards' => [7], 'rank' => 7],
            ['cards' => [8], 'rank' => 8],
        ]);

        Act::playCard(null, 's', ['challenge' => true]);

        expect(Act::get('gameContext')->getCurrentRound()->turns)->toBe([]);
    });
});

describe('Menteur: fin de partie', function () {
    test('Un joueur sans carte est déclaré vainqueur', function () {
        Arrange::setHands([
            '1' => [],
            '2' => [[5, 's']],
        ]);
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
        ]);
        Arrange::setGameStarted();

        expect(Act::isGameFinished())->toBeTrue();
    });

    test("Si tous les joueurs ont encore des cartes, il n'y a pas de vainqueur", function () {
        Arrange::setHands([
            '1' => [[4, 's']],
            '2' => [[5, 's']],
        ]);
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
        ]);
        Arrange::setGameStarted();

        expect(Act::isGameFinished())->toBeFalse();
    });
});

describe('Menteur: events', function () {
    test('Une contestation envoie un événement ChallengeResult avec le bon résultat', function () {
        Arrange::setMenteurRound([
            ['cards' => [7], 'rank' => 8],
        ]);

        Act::playCard(null, 's', ['challenge' => true]);

        $event = firstEventOfType(Act::getEvents(), GameEventTypeEnum::CHALLENGE_RESULT);

        expect($event)->not->toBeNull();
        expect($event->payload['wasLying'])->toBeTrue();
        expect($event->payload['declaredRank'])->toBe('8');
    });
});
