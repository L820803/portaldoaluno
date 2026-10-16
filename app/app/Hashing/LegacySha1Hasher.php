<?php

namespace App\Hashing;

use Illuminate\Contracts\Hashing\Hasher;

/**
 * Hash de senha compatível com o sistema legado (CakePHP), que grava em
 * portal.users.password o valor sha1(salt . senha) em hexadecimal.
 *
 * Mantido para que senhas criadas/alteradas aqui continuem válidas no legado.
 */
class LegacySha1Hasher implements Hasher
{
    public function __construct(protected string $salt = '') {}

    public function info($hashedValue)
    {
        return ['algo' => 'sha1', 'algoName' => 'sha1', 'options' => []];
    }

    public function make($value, array $options = [])
    {
        return sha1($this->salt.$value);
    }

    public function check($value, $hashedValue, array $options = [])
    {
        if (! is_string($hashedValue) || $hashedValue === '') {
            return false;
        }

        return hash_equals(strtolower($hashedValue), $this->make((string) $value));
    }

    public function needsRehash($hashedValue, array $options = [])
    {
        return false;
    }
}
