<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminBroadcastMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $mailSubject,
        public readonly string $body,
    ) {}

    public function build(): self
    {
        return $this->subject($this->mailSubject)
            ->markdown('mail.admin-broadcast', [
                'body' => $this->body,
            ]);
    }
}
