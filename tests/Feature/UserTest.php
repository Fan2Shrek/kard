<?php

use App\Entity\User;

it('anonymizes the account without deleting it', function () {
    $user = new User('phil', 'phil@example.com');
    $user->setPassword('$2y$13$hash');
    $user->addRole('ROLE_ADMIN');

    $user->anonymize();

    expect($user->getUsername())->toStartWith('joueur-supprime-')
        ->and($user->getUsername())->not->toBe('phil')
        ->and($user->getEmail())->toBe('')
        ->and($user->getPassword())->toBe('')
        ->and($user->getRoles())->toBe(['ROLE_USER']);
});

it('generates a different username for each anonymized account', function () {
    $a = new User('a', 'a@example.com');
    $b = new User('b', 'b@example.com');

    $a->anonymize();
    $b->anonymize();

    expect($a->getUsername())->not->toBe($b->getUsername());
});
