<?php

use App\Service\GameManager\GameMode\PresidentGameMode;
use App\Tests\AAA\Act\Act;
use App\Tests\AAA\Arrange\Arrange;

covers(PresidentGameMode::class);

beforeAll(function () {
    Act::addContext('gamePlayer', new PresidentGameMode);
});

pest()->group('Président');

describe('Président: règle basique', function () {
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

    // Les tests de mutations ne passent pas, ce test est obligatoire pour les faire passer
    test('On peut jouer une carte plus basse sur une carte plus haute', function () {
        // Arrange
        Arrange::setCurrentCard(7);

        // Act
        Act::playCard(9, 's');
    })->throwsNoExceptions();

    test('Il est possible de jouer une carte', function () {
        // Arrange
        Arrange::setCurrentCard(7);

        // Act
        Act::playCard(8, 's');
    })->throwsNoExceptions();

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
        Arrange::setCurrentCard(7);

        // Act
        Act::playCards([
            [7, 's'],
            [7, 'h'],
            [7, 'c'],
            [7, 'd'],
        ]);
    })->throws('Invalid number of cards played');
});

describe('Président: carte simple', function () {
    test('On peut jouer une carte plus haute sur une carte plus basse', function () {
        // Arrange
        Arrange::setcurrentcard(7);

        // act
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
    })->throws('A card with a higher or same value must be played');

    test('On ne peut pas jouer un double sur une carte simple', function () {
        // Arrange
        Arrange::setCurrentCard(7);

        // Act
        Act::playCards([
            [7, 's'],
            [7, 'h'],
        ]);
    })->throws('Incorrect number of cards played');
});

describe('Président: début de partie', function () {
    beforeEach(function () {
        Arrange::setGameStarted();
    });

    test('On peut commencer une partie avec une carte simple', function () {
        Act::playCard(7, 's');
    })->throwsNoExceptions();

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
    })->throws("Can't play multiple cards with different values");
});

describe('Président: carte ou rien', function () {
    test('On peut jouer une carte de la bonne valeur sur un valeur ou rien', function () {
        Arrange::setRound([[7], [7]]);

        Act::playCard(7, 'h');
    })->throwsnoexceptions();

    test('On ne peut pas jouer de mauvaise valeur une carte ou rien', function () {
        Arrange::setRound([[7], [7]]);

        Act::playCard(9, 'h');
    })->throws('Can not play "9" when "7" or nothing.');

    test("on ne peut pas jouer de mauvaise valeur une carte ou rien au milieu d'un round", function () {
        arrange::setround([
            [3],
            [5],
            [7],
            [7]
        ]);

        act::playcard(9, 'h');
    })->throws('Can not play "9" when "7" or nothing.');
});
