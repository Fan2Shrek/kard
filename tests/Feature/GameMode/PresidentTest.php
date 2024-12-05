<?php

use App\Service\GameManager\GameMode\PresidentGameMode;
use App\Tests\AAA\Act\Act;
use App\Tests\AAA\Arrange\Arrange;

covers(PresidentGameMode::class);

beforeAll(function () {
    Act::addContext('gamePlayer', new PresidentGameMode);
});

pest()->group('Président');

describe('Président: Carte simple', function () {
    test('on peut jouer une carte plus haute sur une carte plus basse', function () {
        // arrange
        arrange::setcurrentcard(7);

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
    })->throws('A card with a higher value must be played');
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
