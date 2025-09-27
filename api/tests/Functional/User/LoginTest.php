<?php

pest()->group('User');

describe('Connexion', function () {
    test('Il est possible de se connecter', function () {
        $this->createUser(
            username: 'hourglass',
            password: 'secret',
        );

        $response = $this->client->request('POST', '/api/login', [
            'json' => [
                'username' => 'hourglass',
                'password' => 'secret',
            ],
        ]);

        expect($response)->toHaveStatusCode(204);
        expect($response)->toHaveHeader('set-cookie');
    });

    test('On ne peut pas se connecter si on a pas de compte', function () {
        $response = $this->client->request('POST', '/api/login', [
            'json' => [
                'username' => 'non',
                'password' => 'toujours_pas',
            ],
        ]);

        expect($response)->toHaveStatusCode(401);
        expect($response)->not->toHaveHeader('set-cookie');
    });
});
