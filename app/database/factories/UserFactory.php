<?php

namespace Database\Factories;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Hash;

/**
 * Cria usuários de teste no RepositorioUsuarios (mesma API usada antes com o Eloquent).
 */
class UserFactory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /** @var list<array<string, mixed>|Closure> */
    protected array $estados = [];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'login' => fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'validated_email' => true,
            'password' => static::$password ??= Hash::make('password'),
        ];
    }

    /**
     * @param  array<string, mixed>|Closure(array<string, mixed>): array<string, mixed>  $estado
     */
    public function state(array|Closure $estado): static
    {
        $nova = clone $this;
        $nova->estados[] = $estado;

        return $nova;
    }

    /**
     * Aluno que ainda não completou o primeiro acesso: senha = código, sem e-mail.
     */
    public function primeiroAcesso(): static
    {
        return $this->state(fn () => [
            // Avaliado com os atributos finais (inclui o login passado no create)
            'password' => fn (array $attributes) => Hash::make($attributes['login']),
            'email' => null,
            'validated_email' => false,
        ]);
    }

    /**
     * Indicate that the user's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(['validated_email' => false]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function make(array $attributes = []): User
    {
        $final = $this->definition();

        foreach ($this->estados as $estado) {
            $final = array_merge($final, $estado instanceof Closure ? $estado($final) : $estado);
        }

        $final = array_merge($final, $attributes);

        foreach ($final as $chave => $valor) {
            if ($valor instanceof Closure) {
                $final[$chave] = $valor($final);
            }
        }

        return new User($final);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes = []): User
    {
        $user = $this->make($attributes);
        $user->save();

        return $user;
    }
}
