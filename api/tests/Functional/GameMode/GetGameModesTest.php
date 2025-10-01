<?php

use App\Service\GameManager\GameMode\GameModeEnum;
use App\Tests\There\ThereIs;

pest()->group('GameModes');

describe('Lister les modes de jeu', function () {
    test('Il est possible de lister les modes de jeux', function () {
        ThereIs::a()->GameMode()->build();

        $response = $this->client->request('GET', '/api/game_modes');

        expect($response)->toHaveStatusCode(200);
        expect($response)->toHaveCount(1);
    });

    test('Seuls les modes de jeux actifs sont renvoyés', function () {
        ThereIs::some(3)->GameMode()->build();
        ThereIs::some(2)->GameMode()->inactive()->build();

        $response = $this->client->request('GET', '/api/game_modes');

        expect($response)->toHaveCount(3);
    });

    test('Le mode de jeu possède un identifiant', function () {
        ThereIs::a()->GameMode()->for(GameModeEnum::PRESIDENT)->build();

        $response = $this->client->request('GET', '/api/game_modes');

        expect($response->toArray()['member'][0])->toHaveKey('name');
    });

    test('Le mode de jeu possède un nom', function () {
        ThereIs::a()->GameMode()->for(GameModeEnum::PRESIDENT)->build();

        $response = $this->client->request('GET', '/api/game_modes');

        expect($response)->toMatch('[0][name]', 'president');
    });

    test('Le mode de jeu possède une image', function () {
        $gameMode = ThereIs::a()->GameMode()->build();
        ThereIs::a()->GameModeDescription()->withImg('The Pretty Reckless')->for($gameMode)->build();

        $response = $this->client->request('GET', '/api/game_modes');

        expect($response)->toMatch('[0][description][img]', 'The Pretty Reckless');
    });

    test('Le mode de jeu possède une description', function () {
        $gameMode = ThereIs::a()->GameMode()->build();
        ThereIs::a()->GameModeDescription()->withDescription('Make Me Wanna Die')->for($gameMode)->build();

        $response = $this->client->request('GET', '/api/game_modes');

        expect($response)->toMatch('[0][description][description]', 'Make Me Wanna Die');
    });
});
