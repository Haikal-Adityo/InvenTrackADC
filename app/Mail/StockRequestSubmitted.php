<?php

namespace App\Mail;

use App\Models\StockRequest;
use App\Support\InventoryMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class StockRequestSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    /** @var Collection<int, StockRequest> */
    public Collection $stockRequests;

    /**
     * @param Collection<int, StockRequest> $stockRequests
     */
    public function __construct(Collection $stockRequests)
    {
        $stockRequests->each(fn(StockRequest $stockRequest) => $stockRequest->loadMissing(['lines.item', 'user']));
        $this->stockRequests = $stockRequests;
    }

    public function envelope(): Envelope
    {
        $pemohon = $this->stockRequests->first()?->user?->name ?? '-';

        return new Envelope(
            subject: '[' . InventoryMail::appName() . '] Request Stock Barang Baru dari ' . $pemohon,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.stock-request-submitted',
        );
    }
}
