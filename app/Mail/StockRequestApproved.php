<?php

namespace App\Mail;

use App\Models\StockRequest;
use App\Support\InventoryMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StockRequestApproved extends Mailable
{
    use Queueable, SerializesModels;

    public StockRequest $stockRequest;

    public function __construct(StockRequest $stockRequest)
    {
        $this->stockRequest = $stockRequest->loadMissing(['lines.item', 'user', 'processor']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . InventoryMail::appName() . '] Stock Request #' . $this->stockRequest->id . ' Disetujui',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.stock-request-approved',
        );
    }
}
