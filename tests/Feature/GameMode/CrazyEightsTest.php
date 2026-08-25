<?php

use App\Entity\GameMode;
use App\Enum\GameEventTypeEnum;
use App\Game\Exception\RuleException;
use App\Game\Mode\CrazyEightsGameMode;
use App\Game\Mode\GameModeEnum;
use App\Game\Model\Card\Hand;
use App\Tests\AAA\Act\Act;
use App\Tests\AAA\Arrange\Arrange;

covers(CrazyEightsGameMode::class);

beforeEach(function () {
    Act::reset();
    Act::addContext('gamePlayer', new CrazyEightsGameMode());
    Act::addContext('gameMode', new GameMode(GameModeEnum::CRAZY_EIGHTS));
});

pest()->group('Huit');

describe('Huit américain: règles tierces', function () {
    test('On distribue 7 cartes à chaque joueurs', function () {
        expect(Act::draw(4))->toBe(7);
    });

    test('Au début on prend la première carte de la pioche', function () {
        Arrange::setDrawPillSize(4);

        Act::setup();

        expect(Act::get('gameContext')->drawPile->cards)->toHaveCount(3);
        expect(Act::get('gameContext')->getCurrentRound()->getLastTurn()->cardIds)->toHaveCount(1);
    });
});

describe('Huit américain: règles basiques', function () {
    test('Il est possible de jouer une carte', function () {
        Arrange::setCurrentCard(7);

        Act::playCard(9, 's');
    })->throwsNoExceptions();

    test('Jouer une carte la met sur le haut du tas', function () {
        Arrange::setCurrentCard(7);

        Act::playCard(9, 's');

        $lastTurn = Act::get('gameContext')->getCurrentRound()->getLastTurn();

        expect($lastTurn->cardIds)->toBe([Act::card('9', 's')->id]);
    });

    test('Il est possible de jouer une carte sur la même valeur', function () {
        // pas de 8 ici : un 8 est toujours "joker" (voir bloc cartes spéciales),
        // donc ne teste jamais réellement le matching par rang
        Arrange::setCurrentCard(9, 's');

        Act::playCard(9, 'h');
    })->throwsNoExceptions();

    test('Il est possible de jouer une carte sur la même couleur', function () {
        Arrange::setCurrentCard(1, suit: 'h');

        Act::playCard(9, 'h');
    })->throwsNoExceptions();

    test('Il est impossible de jouer ni la couleur ni la valeur est la même', function () {
        Arrange::setCurrentCard(3, 's');

        Act::playCard(9, 'c');
    })->throws('cards.same_rank_or_suit');

    test('Il est impossible de jouer ni la couleur ni la valeur est la même, exception', function () {
        Arrange::setCurrentCard(3, 's');

        try {
            Act::playCard(9, 'c');
        } catch (RuleException $e) {
            expect($e->getParams())->toBe([
                '%rank%' => '3',
                '%suit%' => '♠️',
            ]);
        }
    });

    test('Il est possible de jouer plusieurs cartes si elles ont la même valeur', function () {
        Arrange::setCurrentCard(3, 's');

        Act::playCards([
            [5, 's'],
            [5, 'h'],
        ]);
    })->throwsNoExceptions();

    test('Il est impossible de jouer plusieurs cartes si elles ont la même couleur', function () {
        Arrange::setCurrentCard(5, 's');

        Act::playCards([
            [5, 'd'],
            [7, 'd'],
        ]);
    })->throws('cards.same_rank');

    test('Il est impossible de jouer plusieurs cartes si elles rien en commun', function () {
        Arrange::setCurrentCard(5, 's');

        Act::playCards([
            [5, 'd'],
            [9, 'c'],
        ]);
    })->throws('cards.same_rank');

    test("L'ordre des joueurs est aléatoire", function () {
        $hands = array_fill(0, 6, new Hand([]));

        $order = Act::orderPlayers($hands);

        expect($order)->toContain('1', '2', '3', '4', '5', '6');
        expect($order)->not()->toBe(['1', '2', '3', '4', '5', '6']);
    });

    test('Jouer un coup passe la main au joueur suivant', function () {
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
            ['3', 'Player 3'],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard(5, 's');

        expect(Act::get('gameContext')->currentPlayerId)->toBe('2');
    });

    test('Sauter son tour permet de piocher', function () {
        Arrange::setDrawPillSize(3);
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
        ]);
        Arrange::setHands([
            '1' => [[5, 's']],
            '2' => [[6, 's']],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard(null);

        expect(Act::get('gameContext')->getPlayerStateById('1')->hand->cards)->toHaveCount(2);
    });

    test('Sauter son tour passe au joueur suivant', function () {
        Arrange::setDrawPillSize(3);
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard(null);

        expect(Act::get('gameContext')->currentPlayerId)->toBe('2');
    });

    test('Poser une carte la retire de sa main', function () {
        Arrange::setCurrentCard(5, 's');
        Arrange::setCurrentHand([
            [5, 's'],
            [6, 's'],
        ]);
        Act::playCard(5, 's');

        expect(Act::get('currentHand')->cards)->toHaveCount(1);
    });
});

describe('Huit américain: cartes spéciales', function () {
    test("L'as change de sens", function () {
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
            ['3', 'Player 3'],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard(1, 's');

        expect(Act::get('gameContext')->currentPlayerId)->toBe('3');
    });

    test("Lorsqu'un as est joué, si le nombre de joueur est de 2, la joueur rejoue", function () {
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard(1, 's');

        expect(Act::get('gameContext')->currentPlayerId)->toBe('1');
    });

    test('Le valet saute de tour', function () {
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
            ['3', 'Player 3'],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard('j', 's');

        expect(Act::get('gameContext')->currentPlayerId)->toBe('3');
    });

    test('Poser un deux force le joueur suivant à piocher deux cartes', function () {
        Arrange::setDrawPillSize(3);
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
        ]);
        Arrange::setHands([
            '1' => [[5, 's']],
            '2' => [[6, 's']],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard(2, 's');

        expect(Act::get('gameContext')->getPlayerStateById('2')->hand->cards)->toHaveCount(3);
    });

    test('Poser un deux force le joueur suivant et sauter son tour', function () {
        Arrange::setDrawPillSize(3);
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
        ]);
        Arrange::setHands([
            '1' => [[5, 's']],
            '2' => [[6, 's']],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard(2, 's');

        expect(Act::get('gameContext')->currentPlayerId)->toBe('1');
    });

    test('Poser plusieurs deux force le joueur suivant à piocher deux * nombre de deux cartes', function () {
        Arrange::setDrawPillSize(7);
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
        ]);
        Arrange::setHands([
            '1' => [[2, 's'], [2, 'd'], [2, 'h']],
            '2' => [[6, 's']],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCards([
            [2, 's'],
            [2, 'd'],
            [2, 'h'],
        ]);

        expect(Act::get('gameContext')->getPlayerStateById('2')->hand->cards)->toHaveCount(7);
    });

    test('Poser un deux met à jour le nombre de carte du joueur suivant', function () {
        Arrange::setDrawPillSize(3);
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
        ]);
        Arrange::setHands([
            '1' => [[2, 's']],
            '2' => [[6, 's']],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard(2, 's');

        expect(Act::get('gameContext')->getPlayerStateById('2')->hand->cards)->toHaveCount(3);
    });

    test('Le huit permet de changer de couleur', function () {
        Arrange::setCurrentCard(5, 's');

        Act::playCard(8, 's', ['suit' => 'h']);

        expect(Act::get('gameContext')->getCurrentRound()->getLastTurn()->data['suit'])->toBe('h');
    });

    test("Le huit peut être joué sur n'importe quelle carte", function () {
        Arrange::setCurrentCard(5, 's');

        Act::playCard(8, 's', ['suit' => 's']);
    })->throwsNoExceptions();

    test('Jouer un huit, retire la carte du joueur', function () {
        Arrange::setCurrentCard(5, 's');
        Arrange::setCurrentHand([
            [5, 's'],
            [8, 's'],
        ]);

        Act::playCard(8, 's', ['suit' => 's']);

        expect(Act::get('currentHand')->cards)->toHaveCount(1);
    });

    test('La carte joué après le huit doit être de la couleur demandé', function () {
        Arrange::setCurrentCard(5, 's');

        Act::playCard(8, 's', ['suit' => 'd']);
        Act::playCard(3, 'd');
    })->throwsNoExceptions();

    test('Après un huit, le tour passe au joueur suivant', function () {
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
            ['3', 'Player 3'],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard(8, 's', ['suit' => 'd']);

        expect(Act::get('gameContext')->currentPlayerId)->toBe('2');
    });

    test("La carte joué après le huit ne doit être d'un autre couleur", function () {
        Arrange::setCurrentCard(5, 's');

        Act::playCard(8, 's', ['suit' => 'd']);
        Act::playCard(3, 's');
    })->throws('cards.same_rank_or_suit');

    test("La carte joué après le huit ne doit être d'un autre couleur, paramètre", function () {
        Arrange::setCurrentCard(5, 's');

        Act::playCard(8, 's', ['suit' => 'd']);

        try {
            Act::playCard(3, 's');
        } catch (RuleException $e) {
            expect($e->getParams())->toBe([
                '%rank%' => '8',
                '%suit%' => '♦️',
            ]);
        }
    });
});

describe('Huit américain: events', function () {
    describe('Cartes spéciales', function () {
        test("Lorsqu'un valet est posé, un évenement TURN_SKIPPED est envoyé", function () {
            Arrange::setCurrentCard(7, 'h');

            Act::playCard('j', 'h');

            expect(firstEventOfType(Act::getEvents(), GameEventTypeEnum::TURN_SKIPPED))->not->toBeNull();
        });

        test("Lorsqu'un as est posé, un évenement REVERSE_PLAYERS_ORDER est envoyé", function () {
            Arrange::setCurrentCard(7, 'h');

            Act::playCard(1, 'h');

            expect(firstEventOfType(Act::getEvents(), GameEventTypeEnum::REVERSE_PLAYERS_ORDER))->not->toBeNull();
        });

        test("Lorsqu'un huit est posé, un évenement SUIT_CHANGED est envoyé", function () {
            Arrange::setCurrentCard(7, 'h');

            Act::playCard(8, 's', ['suit' => 's']);

            expect(firstEventOfType(Act::getEvents(), GameEventTypeEnum::SUIT_CHANGED))->not->toBeNull();
        });

        test("L'évenement SuitChanged contient la nouvelle couleur", function () {
            Arrange::setCurrentCard(7, 'h');

            Act::playCard(8, 's', ['suit' => 'h']);

            $event = firstEventOfType(Act::getEvents(), GameEventTypeEnum::SUIT_CHANGED);

            expect($event->payload['suit'])->toBe('h');
        });

        test("Lorsqu'un deux est posé, des évenements CARD_DRAWN sont envoyés", function () {
            Arrange::setDrawPillSize(3);
            Arrange::setPlayers([
                ['1', 'Player 1'],
                ['2', 'Player 2'],
            ]);
            Arrange::setHands([
                '1' => [[5, 's']],
                '2' => [[6, 's']],
            ]);
            Arrange::setCurrentCard(5, 's');

            Act::playCard(2, 's');

            $drawnEvents = array_filter(
                Act::getEvents(),
                fn ($e) => GameEventTypeEnum::CARD_DRAWN === $e->type
            );

            expect($drawnEvents)->toHaveCount(2);
        });

        test("Lorsque plusieurs deux sont posés, le nombre d'évenements CARD_DRAWN est multiplié d'autant", function () {
            Arrange::setDrawPillSize(7);
            Arrange::setPlayers([
                ['1', 'Player 1'],
                ['2', 'Player 2'],
            ]);
            Arrange::setHands([
                '1' => [[2, 's'], [2, 'd'], [2, 'h']],
                '2' => [[6, 's']],
            ]);
            Arrange::setCurrentCard(5, 's');

            Act::playCards([
                [2, 's'],
                [2, 'd'],
                [2, 'h'],
            ]);

            $drawnEvents = array_filter(
                Act::getEvents(),
                fn ($e) => GameEventTypeEnum::CARD_DRAWN === $e->type
            );

            expect($drawnEvents)->toHaveCount(6);
        });
    });

    test("Lorsqu'un joueur pioche, un évenement CARD_DRAWN est envoyé", function () {
        Arrange::setDrawPillSize(3);
        Arrange::setPlayers([
            ['1', 'Player 1'],
            ['2', 'Player 2'],
        ]);
        Arrange::setHands([
            '1' => [[5, 's']],
            '2' => [[6, 's']],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard(null);

        expect(firstEventOfType(Act::getEvents(), GameEventTypeEnum::CARD_DRAWN))->not->toBeNull();
    });
});

describe('Huit américan: fin de partie', function () {
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

    test('Si tous les joueurs ont encore des cartes, la partie continue', function () {
        Arrange::setPlayers([
            ['1', 'Player 1', 3],
            ['2', 'Player 2', 2],
        ]);
        Arrange::setGameStarted();

        $result = Act::isGameFinished();

        expect($result)->toBeFalse();
    });
});
