<?php

use App\Game\Exception\RuleException;
use App\Entity\GameMode;
use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Game\Event\CardDrawnEvent;
use App\Game\Event\CardPlayedEvent;
use App\Game\Event\PlayOrderReversedEvent;
use App\Game\Event\SuitChangedEvent;
use App\Game\Event\TurnSkippedEvent;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\Hand;
use App\Game\Model\Player;
use App\Game\Mode\CrazyEightsGameMode;
use App\Game\Mode\GameModeEnum;
use App\Tests\AAA\Act\Act;
use App\Tests\AAA\Arrange\Arrange;
use App\Tests\Resource\ActHandRepository;

covers(CrazyEightsGameMode::class);

beforeEach(function () {
    Act::reset();
    Act::addContext('gamePlayer', new CrazyEightsGameMode(
        new ActHandRepository(),
    ));
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

        expect(Act::get('gameContext')->getDrawPile())->toHaveCount(3);
        expect(Act::get('gameContext')->getCurrentCards())->toHaveCount(1);
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

        expect(Act::get('gameContext')->getCurrentCards())->toHaveCount(1);
        expect(Act::get('gameContext')->getCurrentCards())->toEqual([new Card(Rank::NINE, Suit::SPADES)]);
    });

    test('Il est possible de jouer une carte sur la même valeur', function () {
        Arrange::setCurrentCard(8);

        Act::playCard(9, 's');
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
        $hands = [
            1 => new Hand([
                new Card(Rank::EIGHT, Suit::SPADES),
                new Card(Rank::EIGHT, Suit::HEARTS),
            ]),
            2 => new Hand([
                new Card(Rank::EIGHT, Suit::SPADES),
                new Card(Rank::QUEEN, Suit::HEARTS),
            ]),
            3 => new Hand([
                new Card(Rank::EIGHT, Suit::SPADES),
                new Card(Rank::KING, Suit::HEARTS),
            ]),
            4 => new Hand([
                new Card(Rank::EIGHT, Suit::SPADES),
                new Card(Rank::KING, Suit::HEARTS),
            ]),
            5 => new Hand([
                new Card(Rank::EIGHT, Suit::SPADES),
                new Card(Rank::KING, Suit::HEARTS),
            ]),
            6 => new Hand([
                new Card(Rank::EIGHT, Suit::SPADES),
                new Card(Rank::KING, Suit::HEARTS),
            ]),
        ];

        $players = Act::orderPlayers($hands);

        expect($players)->toContain(1, 2, 3);
        expect($players)->not()->toBe([1, 2, 3, 4, 5, 6]);
    });

    test('Jouer un coup passe la main au joueur suivant', function () {
        Arrange::setPlayers([
            new Player('1', 'Player 1'),
            new Player('2', 'Player 2'),
            new Player('3', 'Player 3'),
        ]);
        Arrange::setGameStarted();
        Arrange::setCurrentCard(5, 's');

        Act::playCard(5, 's');

        expect(Act::get('gameContext')->getCurrentPlayer()->id)->toBe('2');
    });

    test('Sauter son tour permet de piocher', function () {
        Arrange::setDrawPillSize(3);
        Arrange::setPlayers([
            new Player('1', 'Player 1'),
            new Player('2', 'Player 2'),
        ]);
        Arrange::setHands([
            '1' => [[5, 's']],
            '2' => [[6, 's']],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard(null);

        expect(Act::get('hands')['1']->getCards())->toHaveCount(2);
    });

    test('Sauter son tour passe au joueur suivant', function () {
        Arrange::setDrawPillSize(3);
        Arrange::setPlayers([
            new Player('1', 'Player 1'),
            new Player('2', 'Player 2'),
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard(null);

        expect(Act::get('gameContext')->getCurrentPlayer()->id)->toBe('2');
    });

    test('Poser une carte la retire de sa main', function () {
        Arrange::setCurrentCard(5, 's');
        Arrange::setCurrentHand([
            [5, 's'],
            [6, 's'],
        ]);
        Act::playCard(5, 's');

        expect(Act::get('currentHand'))->toHaveCount(1);
    });
});

describe('Huit américain: cartes spéciales', function () {
    test("L'as change de sens", function () {
        Arrange::setPlayers([
            new Player('1', 'Player 1'),
            new Player('2', 'Player 2'),
            new Player('3', 'Player 3'),
        ]);
        Arrange::setGameStarted();
        Arrange::setCurrentCard(5, 's');

        Act::playCard(1, 's');

        expect(Act::get('gameContext')->getCurrentPlayer()->id)->toBe('3');
    });

    test("Lorsqu'un as est joué, si le nombre de joueur est de 2, la joueur rejoue", function () {
        Arrange::setPlayers([
            new Player('1', 'Player 1'),
            new Player('2', 'Player 2'),
        ]);
        Arrange::setGameStarted();
        Arrange::setCurrentCard(5, 's');

        Act::playCard(1, 's');

        expect(Act::get('gameContext')->getCurrentPlayer()->id)->toBe('1');
    });

    test('Le valet saute de tour', function () {
        Arrange::setPlayers([
            new Player('1', 'Player 1'),
            new Player('2', 'Player 2'),
            new Player('3', 'Player 3'),
        ]);
        Arrange::setGameStarted();
        Arrange::setCurrentCard(5, 's');

        Act::playCard('j', 's');

        expect(Act::get('gameContext')->getCurrentPlayer()->id)->toBe('3');
    });

    test('Poser un deux force le joueur suivant à piocher deux cartes', function () {
        Arrange::setDrawPillSize(3);
        Arrange::setPlayers([
            new Player('1', 'Player 1'),
            new Player('2', 'Player 2'),
        ]);
        Arrange::setHands([
            '1' => [[5, 's']],
            '2' => [[6, 's']],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard(2, 's');

        expect(Act::get('hands')['2'])->toHaveCount(3);
    });

    test('Poser un deux force le joueur suivant et sauter son tour', function () {
        Arrange::setDrawPillSize(3);
        Arrange::setPlayers([
            new Player('1', 'Player 1'),
            new Player('2', 'Player 2'),
        ]);
        Arrange::setHands([
            '1' => [[5, 's']],
            '2' => [[6, 's']],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard(2, 's');

        expect(Act::get('gameContext')->getCurrentPlayer()->id)->toBe('1');
    });

    test('Poser plusieurs deux force le joueur suivant à piocher deux * nombre de deux cartes', function () {
        Arrange::setDrawPillSize(7);
        Arrange::setPlayers([
            new Player('1', 'Player 1'),
            new Player('2', 'Player 2'),
        ]);
        Arrange::setHands([
            '1' => [[5, 's']],
            '2' => [[6, 's']],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCards([
            [2, 's'],
            [2, 'd'],
            [2, 'h'],
        ]);

        expect(Act::get('hands')['2'])->toHaveCount(7);
    });

    test('Poser un deux met à jour le nombre de carte du joueur suivant', function () {
        Arrange::setDrawPillSize(3);
        Arrange::setPlayers([
            new Player('1', 'Player 1'),
            new Player('2', 'Player 2'),
        ]);
        Arrange::setHands([
            '1' => [[2, 's']],
            '2' => [[6, 's']],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard(2, 's');

        expect(Act::get('gameContext')->getNextPlayer()->cardsCount)->toBe(3);
    });

    test('Le huit permet de changer de couleur', function () {
        Arrange::setCurrentCard(5, 's');

        Act::playCard(8, 's', ['name' => 'heart']);

        expect(Act::get('gameContext')->getData('suit'))->toBe(Suit::HEARTS);
    });

    test('La couleur demandé par le huit est insensible à la case', function () {
        Arrange::setCurrentCard(5, 's');

        Act::playCard(8, 's', ['name' => 'HEART']);

        expect(Act::get('gameContext')->getData('suit'))->toBe(Suit::HEARTS);
    });

    test("Le huit peut être joué sur n'importe quelle carte", function () {
        Arrange::setCurrentCard(5, 's');

        Act::playCard(8, 's', ['name' => 'spade']);
    })->throwsNoExceptions();

    test('Jouer un huit, retire la carte du joueur', function () {
        Arrange::setCurrentCard(5, 's');
        Arrange::setCurrentHand([
            [5, 's'],
            [8, 's'],
        ]);

        Act::playCard(8, 's', ['name' => 'spade']);

        expect(Act::get('currentHand'))->toHaveCount(1);
    });

    test('La carte joué après le huit doit être de la couleur demandé', function () {
        Arrange::setCurrentCard(5, 's');

        Act::playCard(8, 's', ['name' => 'diamond']);
        Act::playCard(3, 'd');
    })->throwsNoExceptions();

    test('Après un huit, le tour passe au joueur suivant', function () {
        Arrange::setPlayers([
            new Player('1', 'Player 1'),
            new Player('2', 'Player 2'),
            new Player('3', 'Player 3'),
        ]);
        Arrange::setGameStarted();
        Arrange::setCurrentCard(5, 's');

        Act::playCard(8, 's', ['name' => 'diamond']);

        expect(Act::get('gameContext')->getCurrentPlayer()->id)->toBe('2');
    });

    test("La carte joué après le huit ne doit être d'un autre couleur", function () {
        Arrange::setCurrentCard(5, 's');

        Act::playCard(8, 's', ['name' => 'diamond']);
        Act::playCard(3, 's');
    })->throws('cards.bad_suit');

    test("La carte joué après le huit ne doit être d'un autre couleur, paramètre", function () {
        Arrange::setCurrentCard(5, 's');

        Act::playCard(8, 's', ['name' => 'diamond']);
        try {
            Act::playCard(3, 's');
        } catch (RuleException $e) {
            expect($e->getParams())->toBe([
                '%suit%' => '♦️',
            ]);
        }
    });
});

describe('Huit américain: events', function () {
    describe('Cartes spéciales', function () {
        test("Lorsqu'un valet est posé, un évenement TurnSkipped puis CardPlayed sont envoyés", function () {
            Arrange::setCurrentCard(7, 'h');

            Act::playCard('j', 'h');

            $events = Act::getEvents();

            expect($events)->toHaveCount(2);
            expect($events[0])->toBeInstanceOf(TurnSkippedEvent::class);
            expect($events[1])->toBeInstanceOf(CardPlayedEvent::class);
        });

        test("L'évenement TurnSkipped contient le joueur qui saute son tour", function () {
            Arrange::setPlayers([
                new Player('1', 'Tyler, the Creator'),
                new Player('2', 'After The Storm'),
                new Player('3', 'Kali Uchis'),
            ]);
            Arrange::setCurrentCard(7, 'h');

            Act::playCard('j', 'h');

            expect(Act::getEvents()[0]->player->username)->toBe('After The Storm');
        });

        test("Lorsqu'un as est posé, un évenement PlayOrderReversed puis CardPlayed sont envoyés", function () {
            Arrange::setCurrentCard(7, 'h');

            Act::playCard(1, 'h');

            $events = Act::getEvents();

            expect($events)->toHaveCount(2);
            expect($events[0])->toBeInstanceOf(PlayOrderReversedEvent::class);
            expect($events[1])->toBeInstanceOf(CardPlayedEvent::class);
        });

        test("Lorsqu'un huit est posé, un évenement SuitChanged est envoyé", function () {
            Arrange::setCurrentCard(7, 'h');

            Act::playCard(8, 's', ['name' => 'spade']);

            expect(Act::getEvents())->toHaveCount(1);
            expect(Act::getEvents()[0])->toBeInstanceOf(SuitChangedEvent::class);
        });

        test("L'évenement SuitChanged contient la nouvelle couleur", function () {
            Arrange::setCurrentCard(7, 'h');

            Act::playCard(8, 's', ['name' => 'heart']);

            expect(Act::getEvents()[0]->suit)->toBe(Suit::HEARTS);
        });

        test("Lorsqu'un deux est posé, un évenement CardDrawn forcé puis CardPlayed sont envoyés", function () {
            Arrange::setDrawPillSize(3);
            Arrange::setPlayers([
                new Player('1', 'Player 1'),
                new Player('2', 'Player 2'),
            ]);
            Arrange::setHands([
                '1' => [[5, 's']],
                '2' => [[6, 's']],
            ]);
            Arrange::setCurrentCard(5, 's');

            Act::playCard(2, 's');

            $events = Act::getEvents();
            expect($events)->toHaveCount(2);
            expect($events[0])->toBeInstanceOf(CardDrawnEvent::class);
            expect($events[0]->forced)->toBeTrue();
            expect($events[1])->toBeInstanceOf(CardPlayedEvent::class);
        });

        test("L'évenement CardDrawn contient le joueur forcé de piocher et le nombre de cartes", function () {
            Arrange::setDrawPillSize(5);
            Arrange::setPlayers([
                new Player('1', 'Player 1'),
                new Player('2', 'Player 2'),
            ]);
            Arrange::setHands([
                '1' => [[5, 's']],
                '2' => [[6, 's']],
            ]);
            Arrange::setCurrentCard(7, 'h');

            Act::playCards([
                [2, 'h'],
                [2, 'd'],
            ]);

            $event = Act::getEvents()[0];

            expect($event->player->username)->toBe('Player 2');
            expect($event->count)->toBe(4);
        });
    });

    test("Lorsqu'un joueur pioche, un évenement CardDrawn est envoyé", function () {
        Arrange::setDrawPillSize(3);
        Arrange::setPlayers([
            new Player('1', 'Player 1'),
            new Player('2', 'Player 2'),
        ]);
        Arrange::setHands([
            '1' => [[5, 's']],
            '2' => [[6, 's']],
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard(null);

        $events = Act::getEvents();

        expect($events)->toHaveCount(1);
        expect($events[0])->toBeInstanceOf(CardDrawnEvent::class);
        expect($events[0]->player->username)->toBe('Player 1');
        expect($events[0]->count)->toBe(1);
        expect($events[0]->forced)->toBeFalse();
    });
});

describe('Huit américan: fin de partie', function () {
    test('Un joueur sans carte est déclaré vainqueur', function () {
        Arrange::setPlayers([
            new Player('1', 'Player 1', 3),
            new Player('2', 'Player 2', 0),
        ]);
        Arrange::setGameStarted();

        $result = Act::isGameFinished();

        expect($result)->toBeTrue();
        expect(Act::get('gameContext'))->toHaveWinner('Player 2');
    });

    test('Si tous les joueurs ont encore des cartes, la partie continue', function () {
        Arrange::setPlayers([
            new Player('1', 'Player 1', 3),
            new Player('2', 'Player 2', 2),
        ]);
        Arrange::setGameStarted();

        $result = Act::isGameFinished();

        expect($result)->toBeFalse();
    });
});
