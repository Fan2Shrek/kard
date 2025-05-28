<?php

use App\Entity\GameMode;
use App\Enum\Card\Suit;
use App\Model\Player;
use App\Service\GameManager\GameMode\CrazyEightsGameMode;
use App\Service\GameManager\GameMode\GameModeEnum;
use App\Tests\AAA\Act\Act;
use App\Tests\AAA\Arrange\Arrange;
use App\Tests\Resource\HubSpy;

covers(CrazyEightsGameMode::class);

beforeEach(function () {
    HubSpy::reset();
    Act::addContext('gamePlayer', new CrazyEightsGameMode(
        new HubSpy(),
        $this->createMock(App\Service\Card\HandRepository::class),
        $this->createMock(Symfony\Component\Serializer\SerializerInterface::class)
    ));
    Act::addContext('gameMode', new GameMode(GameModeEnum::CRAZY_EIGHTS));
});

pest()->group('Huit');

describe('Huit américain: règles basiques', function () {
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
    })->throws('Cannot play this card');

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
    })->throws('Cards are unrelated');

    test('Il est impossible de jouer plusieurs cartes si elles rien en commun', function () {
        Arrange::setCurrentCard(5, 's');

        Act::playCards([
            [5, 'd'],
            [9, 'c'],
        ]);
    })->throws('Cards are unrelated');

    test("L'ordre des joueurs est aléatoire", function () {
        $players = [
            new Player('1', 'Player 1'),
            new Player('2', 'Player 2'),
            new Player('3', 'Player 3'),
            new Player('4', 'Player 4'),
        ];

        $players = Act::orderPlayers($players);

        expect($players)->not()->toBe([1, 2, 3, 4]);
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
        Arrange::setRound([
            [7],
        ]);
        Arrange::setCurrentHand([
            [5, 's'],
            [6, 's'],
        ]);

        Act::playCard(null);

        expect(Act::get('currentHand'))->toHaveCount(3);
    });

    test('Sauter son tour passe au joueur suivant', function () {
        Arrange::setPlayers([
            new Player('1', 'Player 1'),
            new Player('2', 'Player 2'),
        ]);
        Arrange::setCurrentCard(5, 's');

        Act::playCard(null);

        expect(Act::get('gameContext')->getCurrentPlayer()->id)->toBe('2');
    });

    test('Poser une carte la retire de sa main', function () {
        Arrange::setcurrentcard(3);
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
        Arrange::setRound([
            [7],
        ]);

        Act::playCard(2, 's');

        // implements tests for multiple 2 cards
        expect(Act::get('currentHand'))->toHaveCount(4);
    })->todo('Seems hard to implements ^^');

    test('Le 8 permet de changer de couleur', function () {
        Arrange::setCurrentCard(5, 's');

        Act::playCard(8, 's', ['name' => 'heart']);

        expect(Act::get('gameContext')->getData('suit'))->toBe(Suit::HEARTS);
    });

    test("Le 8 peut être joué sur n'importe quelle carte", function () {
        Arrange::setCurrentCard(5, 's');

        Act::playCard(8, 's', ['name' => 'spade']);
    })->throwsNoExceptions();

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
    })->throws('Cannot play this card');
});

describe('Huit américain: mercure', function () {
    describe('Cartes spéciales', function () {
        test("Lorsqu'un valet est posé, un évenement est envoyé", function () {
            Arrange::setCurrentCard(7, 'h');

            Act::playCard('j', 'h');

            expect(HubSpy::$published)->toHaveCount(1);
        });

        test("Lorsqu'un valet est posé, un évenement contient un message", function () {
            Arrange::setPlayers([
                new Player('1', 'Tyler, the Creator'),
                new Player('2', 'After The Storm'),
                new Player('3', 'Kali Uchis'),
            ]);
            Arrange::setCurrentCard(7, 'h');

            Act::playCard('j', 'h');

            expectMercureMessage(current(HubSpy::$published))->toBeAction('message');
            expectMercureMessage(current(HubSpy::$published))->toBeHaveData('text', 'Le joueur After The Storm saute son tour');
        });

        test("Lorsqu'un as est posé, un évenement est envoyé", function () {
            Arrange::setCurrentCard(7, 'h');

            Act::playCard(1, 'h');

            expect(HubSpy::$published)->toHaveCount(1);
        });

        test("Lorsqu'un as est posé, un évenement contient un message", function () {
            Arrange::setCurrentCard(7, 'h');

            Act::playCard(1, 'h');

            expectMercureMessage(current(HubSpy::$published))->toBeAction('message');
            expectMercureMessage(current(HubSpy::$published))->toBeHaveData('text', 'Changement de sens !');
        });

        test("Lorsqu'un huit est posé, un évenement est envoyé", function () {
            Arrange::setCurrentCard(7, 'h');

            Act::playCard(8, 's', ['name' => 'spade']);

            expect(HubSpy::$published)->toHaveCount(1);
        });

        test("Lorsqu'un huit est posé, un évenement contient un message", function () {
            Arrange::setCurrentCard(7, 'h');

            Act::playCard(8, 's', ['name' => 'heart']);

            expectMercureMessage(current(HubSpy::$published))->toBeAction('message');
            expectMercureMessage(current(HubSpy::$published))->toBeHaveData('text', 'Changement de couleur en ♥️');
        });

        test("Lorsqu'un deux est posé, un évenement est envoyé", function () {
            Arrange::setCurrentCard(7, 'h');

            Act::playCard(2, 'h');

            expect(HubSpy::$published)->toHaveCount(1);
        })->todo();

        test("Lorsqu'un deux est posé, un évenement contient un message", function () {
            Arrange::setPlayers([
                new Player('1', 'Player 1'),
                new Player('2', 'Player 2'),
            ]);
            Arrange::setCurrentCard(7, 'h');

            Act::playCard(2, 'h');

            expectMercureMessage(current(HubSpy::$published))->toBeAction('message');
            expectMercureMessage(current(HubSpy::$published))->toBeHaveData('text', 'Le joueur Player 2 pioche 2 cartes');
        })->todo('Seems hard to implements ^^');
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
});
