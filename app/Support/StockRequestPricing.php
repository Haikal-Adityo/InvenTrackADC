<?php

namespace App\Support;

use App\Models\StockRequest;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Riwayat harga & referensi untuk form Request Stok Barang.
 *
 * Per barang:
 * - last_sr   : harga terakhir dari Stok Request approved (harga > 0) + nomor dokumennya
 * - last_in   : harga terakhir dari Transaksi IN approved (harga > 0) + tanggalnya
 * - highest   : harga tertinggi dari kedua sumber
 * - reference : Stok Request approved terakhir yang berisi barang tsb (nomor = info Referensi)
 */
final class StockRequestPricing
{
    /**
     * @param  iterable<int>  $itemIds
     * @return array<int, array<string, mixed>>
     */
    public static function forItems(iterable $itemIds): array
    {
        $ids = collect($itemIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        // Baris Stok Request approved (terbaru dulu)
        $srLines = DB::table('stock_request_lines as l')
            ->join('stock_requests as r', 'r.id', '=', 'l.stock_request_id')
            ->whereIn('l.item_id', $ids)
            ->where('r.status', 'approved')
            ->whereNull('r.deleted_at')
            ->orderByDesc('r.created_at')
            ->orderByDesc('r.id')
            ->get(['l.item_id', 'l.price', 'r.id as request_id', 'r.created_at'])
            ->groupBy('item_id');

        // Transaksi IN approved dengan harga (terbaru dulu)
        $inTx = Transaction::query()
            ->whereIn('item_id', $ids)
            ->where('type', 'in')
            ->where('status', 'approved')
            ->where('price', '>', 0)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get(['id', 'item_id', 'price', 'date'])
            ->groupBy('item_id');

        $requestIds = $srLines->flatten(1)->pluck('request_id')->unique();
        $numbers = StockRequest::withTrashed()->whereIn('id', $requestIds)->get(['id', 'created_at'])
            ->mapWithKeys(fn (StockRequest $r) => [$r->id => $r->document_number]);

        $out = [];
        foreach ($ids as $id) {
            /** @var Collection $lines */
            $lines = $srLines->get($id, collect());
            $txs = $inTx->get($id, collect());

            $reference = $lines->first();
            $lastSr = $lines->first(fn ($l) => (int) $l->price > 0);
            $lastIn = $txs->first();

            $candidates = collect();
            $maxSr = $lines->filter(fn ($l) => (int) $l->price > 0)->sortByDesc('price')->first();
            if ($maxSr) {
                $candidates->push(['price' => (int) $maxSr->price, 'source' => 'Stok Request '.($numbers[$maxSr->request_id] ?? '')]);
            }
            $maxIn = $txs->sortByDesc('price')->first();
            if ($maxIn) {
                $candidates->push(['price' => (int) $maxIn->price, 'source' => 'Transaksi IN '.optional($maxIn->date)->format('d/m/Y')]);
            }
            $highest = $candidates->sortByDesc('price')->first();

            $out[$id] = [
                'reference' => $reference ? [
                    'id' => (int) $reference->request_id,
                    'number' => $numbers[$reference->request_id] ?? null,
                ] : null,
                'last_sr' => $lastSr ? [
                    'price' => (int) $lastSr->price,
                    'number' => $numbers[$lastSr->request_id] ?? null,
                ] : null,
                'last_in' => $lastIn ? [
                    'price' => (int) $lastIn->price,
                    'date' => optional($lastIn->date)->format('d/m/Y'),
                ] : null,
                'highest' => $highest,
            ];
        }

        return $out;
    }
}
