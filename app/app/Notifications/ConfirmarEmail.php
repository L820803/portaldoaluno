<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Link para confirmar o e-mail informado pelo aluno.
 * Enviado ao novo endereço (Notification::route), não ao e-mail atual.
 */
class ConfirmarEmail extends Notification
{
    public function __construct(
        public User $user,
        public string $url,
        public int $horas,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirme seu e-mail - Portal do Aluno')
            ->greeting('Olá, '.Saudacao::primeiroNome($this->user->name).'!')
            ->line('Recebemos um pedido para usar este endereço no Portal do Aluno (código '.$this->user->login.').')
            ->action('Confirmar e-mail', $this->url)
            ->line("O link vale por {$this->horas} horas.")
            ->line('Se não foi você, ignore esta mensagem: nada será alterado.')
            ->salutation(Saudacao::assinatura());
    }
}
