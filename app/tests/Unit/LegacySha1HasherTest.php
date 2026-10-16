<?php

use App\Hashing\LegacySha1Hasher;

test('make generates sha1 of salt plus password', function () {
    $hasher = new LegacySha1Hasher('salt');

    expect($hasher->make('secret'))->toBe(sha1('saltsecret'));
});

test('check accepts the right password and rejects the wrong one', function () {
    $hasher = new LegacySha1Hasher('salt');
    $hash = sha1('saltsecret');

    expect($hasher->check('secret', $hash))->toBeTrue()
        ->and($hasher->check('secret', strtoupper($hash)))->toBeTrue()
        ->and($hasher->check('wrong', $hash))->toBeFalse()
        ->and($hasher->check('secret', null))->toBeFalse()
        ->and($hasher->check('secret', ''))->toBeFalse();
});

test('legacy hashes never need rehash', function () {
    expect((new LegacySha1Hasher)->needsRehash(sha1('secret')))->toBeFalse();
});
