<?php

use App\Tests\There\ThereIs;

describe('Leaderboard', function () {
    test('Il est possible de voir le leaderbaord', function () {
		ThereIs::a()->User()->build();

		$response = $this->client->request('GET', '/api/leaderboard');

        expect($response)->toHaveStatusCode(200);
    });

    test('Chaque utilisateur apparait', function () {
		ThereIs::some(2)->User()->build();

		$response = $this->client->request('GET', '/api/leaderboard');

        expect($response)->toHaveCount(2);
    });

    test("Le nom d'utilisateur est renvoyé", function () {
		ThereIs::a()->User()->withUsername('Gorillaz')->build();

		$response = $this->client->request('GET', '/api/leaderboard');

        expect($response)->toMatch('[0][player][username]', 'Gorillaz');
    });

    test("Le nombre de victoire est renvoyée", function () {
		$user = ThereIs::a()->User()->build();
		ThereIs::a()->Result()->withWinner($user)->build();

		$response = $this->client->request('GET', '/api/leaderboard');

        expect($response)->toMatch('[0][winsCount]', 1);
    });

    test("Le nombre de partie jouée est renvoyée", function () {
		$user = ThereIs::a()->User()->build();
		ThereIs::a()->Room()->withStatus('finished')->withOwner($user)->build();
		ThereIs::a()->Room()->withStatus('finished')->withOwner($user)->build();

		$response = $this->client->request('GET', '/api/leaderboard');

        expect($response)->toMatch('[0][gamesCount]', 2);
    });

    test('Seules les parties finis sont comptés', function () {
		$user = ThereIs::a()->User()->build();
		ThereIs::a()->Room()->withStatus('finished')->withOwner($user)->build();
		ThereIs::a()->Room()->withStatus('waiting')->withOwner($user)->build();

		$response = $this->client->request('GET', '/api/leaderboard');

        expect($response)->toMatch('[0][gamesCount]', 1);
    });

    test('Le ratio de victoire est renvoyé', function () {
		$user = ThereIs::a()->User()->build();
		$seconUser = ThereIs::a()->User()->build();
		ThereIs::a()->Result()->withWinner($user)->build();
		$room = ThereIs::a()->Room()->withStatus('finished')->withOwner($user)->addParticipant($seconUser)->build();
		ThereIs::a()->Result()->withWinner($seconUser)->withRoom($room)->build();
		ThereIs::a()->Result()->withWinner($seconUser)->withRoom($room)->build();

		$response = $this->client->request('GET', '/api/leaderboard');

        expect($response)->toMatch('[1][winRate]', 50);
    });

	test('Le classement se fait en fonction du nombre de vitoire', function () {
		$user = ThereIs::a()->User()->withUsername('Eminem')->build();
		$seconUser = ThereIs::a()->User()->withUsername('Without me')->build();
		ThereIs::a()->Result()->withWinner($user)->build();
		ThereIs::a()->Result()->withWinner($user)->build();
		ThereIs::a()->Result()->withWinner($seconUser)->build();

		$response = $this->client->request('GET', '/api/leaderboard');

		expect($response)->toMatch('[0][player][username]', 'Eminem');
		expect($response)->toMatch('[1][player][username]', 'Without me');
	});

	test('Le classement se limite à 10 joueurs', function () {
		ThereIs::some(12)->User()->build();

		$response = $this->client->request('GET', '/api/leaderboard');

		expect($response)->toHaveCount(10);
	});
});
