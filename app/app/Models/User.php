<?php

namespace App\Models;

use App\Notifications\RedefinirSenha;
use App\Services\Usuarios\RepositorioUsuarios;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Notifications\Notifiable;
use JsonSerializable;

/**
 * Usuário do portal, guardado pelo RepositorioUsuarios (sem banco de dados).
 *
 * Atributos: id, name, login, password, email, validated_email.
 * Não há "lembrar de mim", e a senha é sha1 (ver LegacySha1Hasher).
 *
 * @property int|null $id
 * @property string|null $name
 * @property string|null $login
 * @property string|null $password
 * @property string|null $email
 * @property bool $validated_email
 *
 * @implements Arrayable<string, mixed>
 */
class User implements Arrayable, Authenticatable, CanResetPassword, JsonSerializable
{
    use Notifiable;

    protected const ATRIBUTOS = ['id', 'name', 'login', 'password', 'email', 'validated_email'];

    /** @var array<string, mixed> */
    protected array $attributes = ['id' => null, 'name' => null, 'login' => null, 'password' => null, 'email' => null, 'validated_email' => false];

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->forceFill($attributes);
    }

    public static function factory(): UserFactory
    {
        return new UserFactory;
    }

    public static function find(int|string|null $id): ?static
    {
        $registro = is_numeric($id) ? self::repositorio()->find((int) $id) : null;

        return $registro ? new static($registro) : null;
    }

    public static function porLogin(?string $login): ?static
    {
        $registro = $login !== null ? self::repositorio()->porLogin($login) : null;

        return $registro ? new static($registro) : null;
    }

    public static function count(): int
    {
        return count(self::repositorio()->todos());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function create(array $attributes): static
    {
        $user = new static($attributes);
        $user->save();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function forceFill(array $attributes): static
    {
        foreach (array_intersect_key($attributes, array_flip(self::ATRIBUTOS)) as $chave => $valor) {
            $this->attributes[$chave] = match ($chave) {
                'id' => $valor === null ? null : (int) $valor,
                'validated_email' => (bool) $valor,
                default => $valor === null ? null : (string) $valor,
            };
        }

        return $this;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(array $attributes): bool
    {
        return $this->forceFill($attributes)->save();
    }

    public function save(): bool
    {
        $this->forceFill(self::repositorio()->salvar($this->attributes));

        return true;
    }

    public function fresh(): ?static
    {
        return static::find($this->id);
    }

    public function refresh(): static
    {
        return $this->forceFill(self::repositorio()->find((int) $this->id) ?? []);
    }

    public function is(?self $outro): bool
    {
        return $outro !== null && $this->id !== null && $this->id === $outro->id;
    }

    public function getKey(): ?int
    {
        return $this->id;
    }

    /**
     * @param  list<string>|string  $chaves
     * @return array<string, mixed>
     */
    public function only(array|string $chaves): array
    {
        return array_intersect_key($this->attributes, array_flip((array) $chaves));
    }

    /**
     * Sem a senha, como o $hidden do Eloquent.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_diff_key($this->attributes, ['password' => true]);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __get(string $chave): mixed
    {
        return $this->attributes[$chave] ?? null;
    }

    public function __set(string $chave, mixed $valor): void
    {
        $this->forceFill([$chave => $valor]);
    }

    public function __isset(string $chave): bool
    {
        return isset($this->attributes[$chave]);
    }

    /**
     * A verificação de e-mail usa o atributo legado validated_email.
     */
    public function hasVerifiedEmail(): bool
    {
        return (bool) $this->validated_email;
    }

    public function markEmailAsVerified(): bool
    {
        return $this->update(['validated_email' => true]);
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): ?int
    {
        return $this->id;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return (string) $this->password;
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName(): string
    {
        return '';
    }

    /**
     * Chave do token de redefinição de senha.
     * Usa o login porque irmãos podem compartilhar o e-mail do responsável.
     */
    public function getEmailForPasswordReset(): string
    {
        return (string) $this->login;
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new RedefinirSenha($token));
    }

    protected static function repositorio(): RepositorioUsuarios
    {
        return app(RepositorioUsuarios::class);
    }
}
