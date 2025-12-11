<?php

namespace App\Mail;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaskCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Task $task;

    /**
     * Create a new message instance.
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this
            ->subject('Новая задача создана')
            ->view('emails.task_created')
            ->with([
                'task' => $this->task,
                'attachmentUrl' => $this->task->getFirstMediaUrl('attachments'),
            ]);
    }
}

