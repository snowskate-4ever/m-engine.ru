<?php

declare(strict_types=1);

namespace App\Notifications\Music;

use App\Enums\NotificationTopic;
use App\Models\User;
use App\Services\Notifications\NotificationGateway;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PerformerLineupInvitationNotification extends Notification
{
    public function __construct(
        public int $peformerId,
        public int $musicianId,
        public string $peformerName,
        public string $inviterName,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return ['database'];
        }

        return app(NotificationGateway::class)->channels($notifiable, NotificationTopic::LineupInvitation);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('music.profiles', ['tab' => 'musician'], true).'#music-musician-lineup';

        return (new MailMessage)
            ->subject(__('mail.lineup_invitation.subject', ['performer' => $this->peformerName]))
            ->greeting(__('mail.lineup_invitation.greeting', ['name' => $notifiable->name]))
            ->line(__('mail.lineup_invitation.line1', [
                'inviter' => $this->inviterName,
                'performer' => $this->peformerName,
            ]))
            ->action(__('mail.lineup_invitation.action'), $url)
            ->line(__('mail.lineup_invitation.line2'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'peformer_id' => $this->peformerId,
            'musician_id' => $this->musicianId,
            'peformer_name' => $this->peformerName,
            'inviter_name' => $this->inviterName,
        ];
    }
}
