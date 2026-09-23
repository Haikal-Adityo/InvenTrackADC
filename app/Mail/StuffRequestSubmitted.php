<?php

namespace App\Mail;

use App\Models\StuffRequest;
use App\Support\InventoryMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StuffRequestSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public StuffRequest $stuffRequest;

    public function __construct(StuffRequest $stuffRequest)
    {
        $this->stuffRequest = $stuffRequest->loadMissing('lines.item');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . InventoryMail::appName() . '] Permintaan Barang Baru dari ' . $this->stuffRequest->requester_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.stuff-request-submitted',
        );
    }
}
