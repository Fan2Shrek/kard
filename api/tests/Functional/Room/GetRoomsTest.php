<?php

use App\Tests\There\ThereIs;

pest()->group('Room');

describe('Lister les salle en cours', function () {
    test('Il est possible de lister les salles en cours', function () {
        ThereIs::a()->Room()->build();

        $response = $this->client->request('GET', '/api/rooms');

        expect($response)->toHaveStatusCode(200);
        expect($response)->toHaveCount(1);
    });

    test('Il est possible de trier sur le status des salles', function () {
        ThereIs::a()->Room()->withStatus('finished')->build();
        ThereIs::a()->Room()->withStatus('waiting')->build();

        $response = $this->client->request('GET', '/api/rooms?status=waiting');

        expect($response)->toHaveStatusCode(200);
        expect($response)->toHaveCount(1);
    });
});
