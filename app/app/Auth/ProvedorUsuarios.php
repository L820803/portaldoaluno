<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher;

/**
 * Autenticação pelo login (código do aluno) contra o RepositorioUsuarios.
 */
class ProvedorUsuarios implements UserProvider
{
    public function __construct(protected Hasher $hasher) {}

    public function retrieveById($identifier): ?User
    {
        return User::find($identifier);
    }

    /**
     * Sem "lembrar de mim".
     */
    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?User
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token): void {}

    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?User
    {
        return isset($credentials['login']) ? User::porLogin((string) $credentials['login']) : null;
    }

    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        $senha = $credentials['password'] ?? null;

        return $senha !== null && $user->getAuthPassword() !== '' && $this->hasher->check($senha, $user->getAuthPassword());
    }

    /**
     * @param  User  $user
     */
    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false): void
    {
        if (! $this->hasher->needsRehash($user->getAuthPassword()) && ! $force) {
            return;
        }

        $user->update(['password' => $this->hasher->make($credentials['password'])]);
    }
}
