<?php

namespace App\Services\Portal;

use App\Models\User;
use App\Notifications\ConfirmarEmail;
use App\Services\ECidade\ECidade;
use App\Services\ECidade\Exceptions\ECidadeException;
use App\Services\ECidade\Exceptions\ECidadeRequestException;
use App\Services\ECidade\Exceptions\ECidadeUnavailableException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Regras da conta do aluno: primeiro acesso, cadastro completo e e-mail.
 */
class ContaAluno
{
    public function __construct(protected ECidade $ecidade) {}

    public static function ehAluno(?User $user): bool
    {
        return $user !== null && ctype_digit((string) $user->login);
    }

    /**
     * Primeiro acesso (UsersController::login do legado): cria o usuário local
     * a partir do e-cidade, desde que a senha digitada seja o próprio código.
     *
     * @throws ECidadeUnavailableException
     * @throws ECidadeRequestException quando o código não é de um aluno
     */
    public function criarNoPrimeiroAcesso(string $codigo, string $senha): ?User
    {
        if (! ctype_digit($codigo) || $senha !== $codigo || User::porLogin($codigo) !== null) {
            return null;
        }

        $dados = $this->ecidade->alunoLoginDados($codigo);
        $email = filter_var(trim((string) ($dados['email'] ?? '')), FILTER_VALIDATE_EMAIL);

        return User::create([
            'login' => $codigo,
            'name' => trim((string) ($dados['nome'] ?? '')) ?: $codigo,
            'password' => Hash::make($codigo),
            // E-mail cadastrado pela escola: confiável, mas ainda não confirmado pelo aluno
            'email' => $email ? mb_strtolower($email) : null,
            'validated_email' => false,
        ]);
    }

    /**
     * Ao entrar: atualiza os dados do aluno a partir do e-cidade.
     * Devolve uma mensagem de erro quando o aluno não pode entrar.
     */
    public function aoEntrar(User $user): ?string
    {
        if (! self::ehAluno($user)) {
            return null;
        }

        try {
            $dados = $this->ecidade->fresh()->alunoDados($user->login);
        } catch (ECidadeUnavailableException) {
            // Entra mesmo assim; o layout avisa que os dados estão indisponíveis
            return null;
        } catch (ECidadeRequestException) {
            return 'Não foi possível obter seus dados no sistema da Secretaria de Educação. Procure a secretaria da sua escola.';
        }

        $nome = trim((string) ($dados['nome_aluno'] ?? ''));

        if ($nome !== '' && $nome !== $user->name) {
            $user->forceFill(['name' => $nome])->save();
        }

        return null;
    }

    /**
     * Ainda usa a senha inicial (= código do aluno)?
     */
    public function usaSenhaInicial(User $user): bool
    {
        return self::ehAluno($user) && Hash::check($user->login, (string) $user->password);
    }

    public function precisaEmail(User $user): bool
    {
        return self::ehAluno($user) && trim((string) $user->email) === '';
    }

    public function cadastroCompleto(User $user): bool
    {
        return ! $this->usaSenhaInicial($user) && ! $this->precisaEmail($user);
    }

    /**
     * Data de nascimento (Y-m-d) do aluno no e-cidade, para o primeiro acesso.
     *
     * @throws ECidadeException
     */
    public function dataNascimento(User $user): ?string
    {
        $data = (string) ($this->ecidade->alunoDados($user->login)['data_nascimento_aluno'] ?? '');

        return preg_match('/^\d{4}-\d{2}-\d{2}/', $data) ? substr($data, 0, 10) : null;
    }

    /**
     * Envia o link de confirmação para o novo e-mail. O e-mail só é gravado
     * quando o aluno clica no link (como no legado).
     */
    public function solicitarTrocaEmail(User $user, string $email): void
    {
        $email = mb_strtolower(trim($email));
        $horas = (int) config('portal.email_link_horas', 24);

        $url = URL::temporarySignedRoute('email.confirmar', now()->addHours($horas), [
            'user' => $user->id,
            'email' => $email,
            // Invalida o link se o e-mail mudar antes da confirmação
            'atual' => $this->marcaEmail($user),
        ]);

        Cache::put($this->chavePendente($user), $email, now()->addHours($horas));

        Notification::route('mail', $email)->notify(new ConfirmarEmail($user, $url, $horas));
    }

    public function emailPendente(User $user): ?string
    {
        return Cache::get($this->chavePendente($user));
    }

    /**
     * Grava o e-mail confirmado pelo link. Falso se o link ficou obsoleto.
     */
    public function confirmarEmail(User $user, string $email, string $marca): bool
    {
        if (! hash_equals($this->marcaEmail($user), $marca) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $user->forceFill(['email' => mb_strtolower($email), 'validated_email' => true])->save();
        Cache::forget($this->chavePendente($user));

        return true;
    }

    protected function marcaEmail(User $user): string
    {
        return substr(hash('sha256', $user->id.'|'.mb_strtolower((string) $user->email)), 0, 16);
    }

    protected function chavePendente(User $user): string
    {
        return 'conta:email-pendente:'.$user->id;
    }
}
