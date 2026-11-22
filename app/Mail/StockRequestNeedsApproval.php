<?php

namespace App\Mail;

use App\Models\StockRequest;
use App\Support\InventoryMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Tahap 2 Stok Request Umum: sudah diverifikasi admin → manajer diminta menyetujui. */
class StockRequestNeedsApproval extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public StockRequest $stockRequest)
    {
        $this->stockRequest->loadMissing(['lines.item', 'user', 'verifier']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '['.InventoryMail::appName().'] Stok Request '.$this->stockRequest->document_number.' Menunggu Persetujuan',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.stock-request-needs-approval');
    }
}
