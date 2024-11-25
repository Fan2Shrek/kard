<?php

use App\Service\GameManager\GameMode\PresidentGameMode;
use App\Tests\AAA\Act\Act;
use App\Tests\AAA\Arrange\Arrange;

covers(PresidentGameMode::class);

beforeAll(function () {
    Act::addContext('gamePlayer', new PresidentGameMode());
});


describe('Président', function () {
    test('On peut jouer une carte plus haute sur une carte plus basse', function () {
        // Arrange
        Arrange::setCurrentCard('7');

        // Act
        Act::playCard('8', 's');
    })->throwsNoExceptions();

    test('On ne peut pas jouer une carte plus basse sur une carte plus haute', function () {
        // Arrange
        Arrange::setCurrentCard('7');

        // Act
        Act::playCard('3', 's');
    })->throws('A card with a higher value must be played');
});
