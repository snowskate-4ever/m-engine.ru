<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\UserAiScheduledItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class AiScheduledReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public UserAiScheduledItem $item) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->item->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'mail.ai-scheduled-reminder-plain',
        );
    }
}
