<?php

namespace App\Mail;

use App\Models\StockOpname;
use App\Support\InventoryMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Stok opname baru diajukan → admin bidang diminta memverifikasi. */
class StockOpnameSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public StockOpname $stockOpname)
    {
        $this->stockOpname->loadMissing(['lines.item', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '['.InventoryMail::appName().'] Stok Opname '.$this->stockOpname->document_number.' Butuh Verifikasi');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.stock-opname-submitted');
    }
}
