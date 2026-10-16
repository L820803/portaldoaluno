<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Link de redefinição de senha. O link leva o login (e não o e-mail),
 * porque irmãos podem compartilhar o e-mail do responsável.
 */
class RedefinirSenha extends Notification
{
    public function __construct(public string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutos = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Redefinição de senha - Portal do Aluno')
            ->greeting('Olá, '.Saudacao::primeiroNome($notifiable->name).'!')
            ->line('Recebemos um pedido para redefinir a senha do Portal do Aluno (código '.$notifiable->login.').')
            ->action('Criar nova senha', route('password.reset', ['token' => $this->token, 'login' => $notifiable->login]))
            ->line("O link vale por {$minutos} minutos e só pode ser usado uma vez.")
            ->line('Se não foi você, ignore esta mensagem: sua senha continua a mesma.')
            ->salutation(Saudacao::assinatura());
    }
}
