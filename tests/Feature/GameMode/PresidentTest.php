<?php

use App\Entity\GameMode;
use App\Enum\GameEventTypeEnum;
use App\Game\Exception\RuleException;
use App\Game\Model\Card\Hand;
use App\Game\Mode\GameModeEnum;
use App\Game\Mode\PresidentGameMode;
use App\Tests\AAA\Act\Act;
use App\Tests\AAA\Arrange\Arrange;

covers(PresidentGameMode::class);

beforeEach(function () {
    Act::reset();
    Act::addContext('gamePlayer', new PresidentGameMode());
    Act::addContext('gameMode', new GameMode(GameModeEnum::PRESIDENT));
});

pest()->group('Président');

describe('Président: règles basiques', function () {
    test("L'ordre est 3, 4, 5, 6, 7, 8, 9, 10, valet, dame, roi, as, 2", function () {
        Arrange::setCurrentCard(3);

        Act::playCard(4, 's');
        Act::playCard(5, 's');
        Act::playCard(6, 's');
        Act::playCard(7, 's');
        Act::playCard(8, 's');
        Act::playCard(9, 's');
        Act::playCard(10, 's');
        Act::playCard('j', 's');
        Act::playCard('q', 's');
        Act::playCard('k', 's');
        Act::playCard(1, 's');
        Act::playCard(2, 's');
    })->throwsNoExceptions();

    test('Il est possible de jouer une carte', function () {
        // Arrange
        Arrange::setCurrentCard(7);

        // Act
        Act::playCard(8, 's');
    })->throwsNoExceptions();

    test('Par défaut, il y a un ordre des joueurs', function () {
        Arrange::setCurrentCard(7);

        Act::playCard(8, 's');

        expect(Act::get('gameContext')->everyoneCanPlay())->toBeFalse();
    });

    test('Il est possible de jouer 2 cartes', function () {
        // Arrange
        Arrange::setCurrentCards([7, 7]);

        // Act
        Act::playCards([
            [8, 's'],
            [8, 'h'],
        ]);
    })->throwsNoExceptions();

    test('Il est possible de jouer 3 cartes', function () {
        // Arrange
        Arrange::setCurrentCards([7, 7, 7]);

        // Act
        Act::playCards([
            [8, 's'],
            [8, 'h'],
            [8, 'c'],
        ]);
    })->throwsNoExceptions();

    test('Il est impossible de jouer 4 cartes ou plus', function () {
        // Arrange
        Arrange::setCurrentCards([6, 6, 6, 6]);

        // Act
        Act::playCards([
            [7, 's'],
            [7, 'h'],
            [7, 'c'],
            [7, 'd'],
        ]);
    })->throws('card.count.invalid');

    test('La dame de coeur commence', function () {
        $hands = [
            new Hand([Act::card('8', 's')->id, Act::card('8', 'h')->id]),
            new Hand([Act::card('8', 'c')->id, Act::card('q', 'h')->id]),
            new Hand([Act::card('8', 'd')->id, Act::card('k', 'h')->id]),
        ];

        $order = Act::orderPlayers($hands);

        // hand at index 1 (player '2') holds the queen of hearts, so it goes first
        expect($order)->toBe(['2', '1', '3']);
    });

    test("Jouer une carte avance d'un tour", function () {
        Arrange::setRound([
            [7],
            [8],
            [9],
        ]);

        Act::playCard(10, 's');

        expect(Act::get('gameContext'))->toHaveTurns(4);
    });

    test('Jouer une carte pass au prochain joueur', function () {
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
        ]);
        Arrange::setGameStarted();

        Act::playCard(10, 's');

        expect(Act::get('gameContext')->currentPlayerId)->toBe('2');
    });

    test('Finir un tour ne passe pas au prochain joueur', function () {
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
        ]);
        Arrange::setCurrentCard(3);

        Act::playCard(2, 's');

        expect(Act::get('gameContext')->currentPlayerId)->toBe('1');
    });

    test('Il est possible de passer son tour', function () {
        // 3 joueurs : avec seulement 2 joueurs, passer termine systématiquement
        // le round (il ne reste plus personne pour potentiellement le relancer) -
        // ce test veut juste vérifier qu'un passe fonctionne sans terminer le round.
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
            ['3', 'Player 3'],
        ]);
        Arrange::setRound([
            [7],
        ]);

        Act::playCard(null);

        expect(Act::get('gameContext'))->toHaveTurns(2);
        expect(Act::get('gameContext')->currentPlayerId)->toBe('2');
    });

    test('Si tous les joueurs passent, le tour se fini', function () {
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
            ['3', 'Player 3'],
            ['4', 'Player 4'],
        ]);

        Arrange::setGameStarted();

        Act::playCard(10);
        Act::playCard(null);
        Act::playCard(null);
        Act::playCard(null);

        expect(Act::get('gameContext'))->toHaveNewRound();
        expect(Act::get('gameContext')->currentPlayerId)->toBe('1');
    });

    test('Finir un tour ajoute les cartes à la défausse', function () {
        Arrange::setRound([
            [7],
            [8],
            [9],
        ]);

        Act::playCard(2, 's');

        expect(Act::get('gameContext')->discardPile->cards)->toHaveCount(4);
    });

    test('Un joueur sans carte est déclaré vainqueur', function () {
        Arrange::setPlayers([
            ['1', 'Player 1', 3],
            ['2', 'Player 2', 0],
        ]);
        Arrange::setGameStarted();

        $result = Act::isGameFinished();

        expect($result)->toBeTrue();
        expect(Act::get('gameContext'))->toHaveWinner('Player 2');
    });

    test("Si tous les joueurs ont encore des cartes, il n'y a pas de vainqueur", function () {
        Arrange::setPlayers([
            ['1', 'Player 1', 21],
            ['2', 'Player 2', 13],
        ]);
        Arrange::setGameStarted();

        $result = Act::isGameFinished();

        expect($result)->toBeFalse();
        expect(array_filter(
            Act::get('gameContext')->players,
            fn ($p) => 0 === $p->score
        ))->toBeEmpty();
    });

    test('Poser une carte la retire de sa main', function () {
        Arrange::setcurrentcard(3);
        Arrange::setCurrentHand([
            [5, 's'],
            [6, 's'],
        ]);
        Act::playCard(5, 's');

        expect(Act::get('currentHand')->cards)->toHaveCount(1);
    });
});

describe('Président: carte simple', function () {
    test('On peut jouer une carte plus haute sur une carte plus basse', function () {
        // Arrange
        Arrange::setcurrentcard(7);

        // Act
        Act::playcard(8, 's');
    })->throwsNoExceptions();

    test('On peut jouer une carte sur une carte de même valeur', function () {
        // Arrange
        Arrange::setCurrentCard(4);

        // Act
        Act::playCard(4, 's');
    })->throwsNoExceptions();

    test('On ne peut pas jouer une carte plus basse sur une carte plus haute', function () {
        // Arrange
        Arrange::setCurrentCard(7);

        // Act
        Act::playCard(3, 's');
    })->throws('card.value.higher');

    test('On ne peut pas jouer un double sur une carte simple', function () {
        // Arrange
        Arrange::setCurrentCard(7);

        // Act
        Act::playCards([
            [7, 's'],
            [7, 'h'],
        ]);
    })->throws('card.count.invalid');
});

describe('Président: cartes doubles', function () {
    test('On peut jouer un double plus haut sur un double plus bas', function () {
        Arrange::setcurrentcards([
            7,
            7,
        ]);

        Act::playcards([[8], [8]]);
    })->throwsNoExceptions();

    test('On peut jouer un double sur un double de même valeur', function () {
        Arrange::setcurrentcards([
            7,
            7,
        ]);

        Act::playcards([[7], [7]]);
    })->throwsNoExceptions();

    test('On ne peut pas jouer un double plus bas sur un double plus haut', function () {
        Arrange::setcurrentcards([
            7,
            7,
        ]);

        Act::playcards([[3], [3]]);
    })->throws('card.value.higher');

    test('On ne peut pas jouer un double avec deux valeurs différentes', function () {
        Arrange::setcurrentcards([
            4,
            4,
        ]);

        Act::playcards([[5], [7]]);
    })->throws('card.values.not_same');

    test('On ne peut pas jouer une carte simple sur un double', function () {
        Arrange::setcurrentcards([
            7,
            7,
        ]);

        Act::playCard(3, 's');
    })->throws('card.count.invalid');
});

describe('Président: cartes triples', function () {
    test('On peut jouer un triple plus haut sur un triple plus bas', function () {
        Arrange::setcurrentcards([
            7,
            7,
            7,
        ]);

        Act::playcards([[8], [8], [8]]);
    })->throwsNoExceptions();

    test('On ne peut pas jouer un triple plus bas sur un triple plus haut', function () {
        Arrange::setcurrentcards([
            7,
            7,
            7,
        ]);

        Act::playcards([[3], [3], [3]]);
    })->throws('card.value.higher');

    test('On ne peut pas jouer un triple avec des valeurs différentes', function () {
        Arrange::setcurrentcards([
            4,
            4,
            4,
        ]);

        Act::playcards([[5], [6], [7]]);
    })->throws('card.values.not_same');

    test('On ne peut pas jouer une carte simple sur un triple', function () {
        Arrange::setcurrentcards([
            7,
            7,
            7,
        ]);

        Act::playCard(3, 's');
    })->throws('card.count.invalid');
});

describe('Président: début de partie', function () {
    beforeEach(function () {
        Arrange::setGameStarted();
    });

    test('On peut commencer une partie avec une carte simple', function () {
        Act::playCard(7, 's');
    })->throwsNoExceptions();

    test('Commencer avec un deux finit le tour', function () {
        Act::playCard(2, 's');

        expect(Act::get('gameContext'))->toHaveNewRound();
    });

    test('On peut pas commencer une partie en jouant rien', function () {
        Act::playCard(null);
    })->throws('turn.first.at_least_one_card');

    test('On peut commencer une partie avec un double de cartes à la même valeur', function () {
        Act::playCards([
            [7, 's'],
            [7, 'h'],
        ]);
    })->throwsNoExceptions();

    test('On peut commencer une partie avec un triple de cartes à la même valeur', function () {
        Act::playCards([
            [7, 's'],
            [7, 'h'],
            [7, 'c'],
        ]);
    })->throwsNoExceptions();

    test('On ne peut pas commencer une partie avec un double de cartes avec des valeurs différentes', function () {
        Act::playCards([
            [7, 's'],
            [8, 'h'],
        ]);
    })->throws('card.values.not_same');
});

describe('Président: carte ou rien', function () {
    test('On peut jouer une carte de la bonne valeur sur un valeur ou rien', function () {
        Arrange::setRound([[7], [7]]);

        Act::playCard(7, 'h');
    })->throwsNoExceptions();

    test('On ne peut pas jouer de mauvaise valeur une carte ou rien', function () {
        Arrange::setRound([[7], [7]]);

        Act::playCard(9, 'h');
    })->throws('card.or_nothing');

    test("On ne peut pas jouer de mauvaise valeur une carte ou rien au milieu d'un round", function () {
        Arrange::setRound([
            [3],
            [5],
            [7],
            [7],
        ]);

        Act::playCard(9, 'h');
    })->throws('card.or_nothing');

    test("Les valeurs des cartes sont passé à l'exception", function () {
        Arrange::setRound([
            [3],
            [5],
            [7],
            [7],
        ]);

        try {
            Act::playCard(9, 'h');
        } catch (RuleException $e) {
            expect($e->getParams())->toBe([
                '%played_card%' => '9',
                '%actual_card%' => '7',
            ]);
        }
    });

    test("Passer son tour et remmetre la même valeur lance l'effet", function () {
        Arrange::setRound([
            [3],
            [5],
            [7],
            [],
            [7],
        ]);

        Act::playCard(9, 'h');
    })->throws('card.or_nothing');

    test("Passer son tour et remmetre la même valeur relance l'effet", function () {
        Arrange::setRound([
            [3],
            [5],
            [7],
            [7],
            [],
            [7],
        ]);

        Act::playCard(9, 'h');
    })->throws('card.or_nothing');

    test('Le lock "carte ou rien" persiste même après un tour passé', function () {
        Arrange::setRound([
            [7],
            [7],
            [],
        ]);

        Act::playCard(9, 'h');
    })->throws('card.or_nothing');

    test('Après un appel aux quatres, le joueurs qui a fini reprends', function () {
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
            ['3', 'Player 3'],
        ]);
        Arrange::setGameStarted();
        Arrange::setRound([
            [3],
            [5],
            [7],
            [7],
            [7],
        ]);

        Act::playCard(7, 's');

        expect(Act::get('gameContext')->currentPlayerId)->toBe('1');
    });
});

describe('Président: fin de tour', function () {
    test('Il est possible de finir un tour en jouant un 2', function () {
        Arrange::setRound([
            [7],
            [8],
            [9],
        ]);

        Act::playCard(2, 's');
        Act::playCard(5, 's');
    })->throwsNoExceptions();

    test('Le tour se termine si un joueur joue un 2', function () {
        Arrange::setRound([
            [7],
            [8],
            [9],
        ]);

        Act::playCard(2, 's');

        expect(Act::get('gameContext'))->toHaveNewRound();
    });

    test('Jouer un deux, le retire de la main du joueur', function () {
        Arrange::setCurrentCard(5, 's');
        Arrange::setCurrentHand([
            [5, 's'],
            [2, 's'],
        ]);
        Act::playCard(2, 's');

        expect(Act::get('currentHand')->cards)->toHaveCount(1);
    });

    test('Le tour se termine si un joueur joue un double 2', function () {
        Arrange::setRound([
            [7, 7],
            [8, 8],
            [9, 9],
        ]);

        Act::playCards([
            [2, 's'],
            [2, 'h'],
        ]);

        expect(Act::get('gameContext'))->toHaveNewRound();
    });

    test('Le tour se termine si un joueur joue un triple 2', function () {
        Arrange::setRound([
            [7, 7, 7],
            [8, 8, 8],
            [9, 9, 9],
        ]);

        Act::playCards([
            [2, 's'],
            [2, 'h'],
            [2, 'd'],
        ]);

        expect(Act::get('gameContext'))->toHaveNewRound();
    });

    test('Un triple ne fini pas le tour', function () {
        Arrange::setRound([
            [3],
            [4],
            [4],
            [],
        ]);

        Act::playCard(4);

        expect(Act::get('gameContext'))->toHaveTurns(5);
    });

    test('Un carré de simple fini le tour', function () {
        Arrange::setRound([
            [3],
            [4],
            [4],
            [4],
        ]);

        Act::playCard(4);

        expect(Act::get('gameContext'))->toHaveNewRound();
    });

    test("4 cartes simples de même rang jouées d'affilée finissent le round", function () {
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
            ['3', 'Player 3'],
            ['4', 'Player 4'],
        ]);
        Arrange::setGameStarted();

        Act::playCard(4);
        Act::playCard(4);
        Act::playCard(4);
        Act::playCard(4);

        expect(Act::get('gameContext'))->toHaveNewRound();
    });

    test('4 simples de même rang avec un tour passé au milieu finissent quand même le round', function () {
        Arrange::setRound([
            [6],
            [6],
            [],
            [6],
        ]);

        Act::playCard(6);

        expect(Act::get('gameContext'))->toHaveNewRound();
    });

    test('Un carré de double fini le tour', function () {
        Arrange::setRound([
            [3, 3],
            [4, 4],
        ]);

        Act::playCards([
            [4],
            [4],
        ]);

        expect(Act::get('gameContext'))->toHaveNewRound();
    });

    test('Un carré de double fini le tour, même avec des tours entre deux', function () {
        Arrange::setRound([
            [3, 3],
            [4, 4],
            [],
        ]);

        Act::playCards([
            [4],
            [4],
        ]);

        expect(Act::get('gameContext'))->toHaveNewRound();
    });
});

describe('Président: events', function () {
    describe('Carte ou rien', function () {
        test("Lorsqu'une carte ou rien est déclarée, un évenement CardOrNothingCalled est envoyé", function () {
            Arrange::setCurrentCard(7);

            Act::playCard(7, 'h');

            $event = firstEventOfType(Act::getEvents(), GameEventTypeEnum::CARD_OR_NOTHING_CALLED);

            expect($event)->not->toBeNull();
        });

        test('L\'évenement CardOrNothingCalled contient le rang concerné', function () {
            Arrange::setCurrentCard(7);

            Act::playCard(7, 'h');

            $event = firstEventOfType(Act::getEvents(), GameEventTypeEnum::CARD_OR_NOTHING_CALLED);

            expect($event->payload['rank'])->toBe('7');
        });

        test("L'évenement CardOrNothingCalled est envoyé même au milieu d'un round", function () {
            Arrange::setRound([
                [6],
                [8],
                [9],
            ]);

            Act::playCard(9, 'h');

            $event = firstEventOfType(Act::getEvents(), GameEventTypeEnum::CARD_OR_NOTHING_CALLED);

            expect($event->payload['rank'])->toBe('9');
        });

        test("Si un deux est joué par dessus une carte ou rien invalide, aucun évenement n'est envoyé", function () {
            Arrange::setRound([
                [3],
                [5],
                [7],
                [7],
            ]);

            try {
                Act::playCard(2, 'h');
            } catch (RuleException $e) {
            }

            expect(Act::getEvents())->toHaveCount(0);
        });
    });

    describe("Fin d'un tour", function () {
        test("A la fin d'un tour, un événement RoundEnded est envoyé", function () {
            Arrange::setCurrentCard(7);

            Act::playCard(2, 'h');

            $event = firstEventOfType(Act::getEvents(), GameEventTypeEnum::ROUND_ENDED);

            expect($event)->not->toBeNull();
        });
    });
});
