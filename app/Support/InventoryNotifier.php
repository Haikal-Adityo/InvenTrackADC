<?php

namespace App\Support;

use App\Models\Item;
use App\Models\StockRequest;
use App\Models\StuffRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Notifikasi (database → otomatis ikut Web Push) per event bisnis NextLog.
 *
 *   Admin bidang   : transaksi baru pending, permintaan barang baru, stok request baru (Umum), stok rendah
 *   Manajer bidang : stok request baru (Teknik), akun baru menunggu approval
 *   Staf bidang    : permintaan barang disetujui (siap diserahkan)
 *   Pembuat        : transaksi / stok request miliknya disetujui atau ditolak
 *   Semua          : notifikasi "tugas" otomatis ditandai dibaca setelah tugasnya diproses
 *
 * Pelaku aksi tidak menerima notifikasi atas aksinya sendiri. Superadmin tidak menerima
 * notifikasi tugas. Semua method aman dipanggil: kegagalan hanya dicatat di log.
 */
final class InventoryNotifier
{
    /* ───────────── Transaksi ───────────── */

    /** Transaksi baru berstatus pending (Umum) → admin bidang. */
    public static function transaksiBaru(Transaction $tx): void
    {
        if ($tx->status !== 'pending') {
            return;
        }

        self::safe(function () use ($tx) {
            $tx->loadMissing(['item', 'user']);
            $oleh = $tx->user?->name ?? 'Input publik';

            self::send(
                self::users(['admin'], $tx->bidang),
                new AppNotification(
                    'Transaksi baru menunggu approval',
                    self::join([self::txLabel($tx), $oleh]),
                    'dashboard',
                    ['kind' => 'tx_pending', 'transaction_id' => $tx->id],
                )
            );
        });
    }

    /**
     * Transaksi disetujui/ditolak (satuan atau per tanggal).
     *
     * @param  iterable<Transaction>  $transactions
     */
    public static function transaksiDiproses(iterable $transactions, string $status): void
    {
        self::safe(function () use ($transactions, $status) {
            $txs = collect($transactions);
            if ($txs->isEmpty()) {
                return;
            }

            self::markRead('tx_pending', 'transaction_id', $txs->pluck('id')->all());

            $label = $status === 'approved' ? 'disetujui' : 'ditolak';
            $actorId = auth()->id();

            $txs->filter(fn (Transaction $t) => $t->user_id && (int) $t->user_id !== (int) $actorId)
                ->groupBy('user_id')
                ->each(function (Collection $group, $userId) use ($label) {
                    $owner = User::find($userId);
                    if (! $owner) {
                        return;
                    }
                    [$title, $body] = $group->count() === 1
                        ? ["Transaksi Anda {$label}", self::txLabel($group->first())]
                        : ["{$group->count()} transaksi Anda {$label}", \Illuminate\Support\Str::limit(
                            $group->map(fn (Transaction $t) => $t->item?->name)->filter()->unique()->implode(', '), 140
                        )];

                    $owner->notify(new AppNotification($title, $body, 'transactions', ['kind' => 'tx_result']));
                });
        });
    }

    /* ───────────── Stok rendah ───────────── */

    /**
     * Cek item yang baru berkurang stoknya → admin bidang (sekali per item sampai notifnya dibaca).
     *
     * @param  iterable<int>  $itemIds
     */
    public static function cekStokRendah(iterable $itemIds): void
    {
        self::safe(function () use ($itemIds) {
            $ids = collect($itemIds)->filter()->unique()->values();
            if ($ids->isEmpty()) {
                return;
            }

            foreach (Item::query()->whereIn('id', $ids)->get() as $item) {
                if (! $item->is_low_stock) {
                    continue;
                }

                $admins = self::users(['admin'], $item->bidang, exceptActor: false)
                    ->reject(fn (User $u) => self::hasUnread($u, 'low_stock', 'item_id', $item->id));

                $stock = $item->current_stock;
                self::send($admins, new AppNotification(
                    $stock <= 0 ? 'Stok habis' : 'Stok rendah',
                    self::join([$item->name, "sisa {$stock} {$item->unit}", "min. {$item->min_stock}"]),
                    $item->bidang === 'teknik' ? 'items' : 'stock',
                    ['kind' => 'low_stock', 'item_id' => $item->id],
                ));
            }
        });
    }

    /* ───────────── Stok Request ───────────── */

    /** @param  iterable<StockRequest>  $stockRequests */
    public static function stokRequestBaru(iterable $stockRequests): void
    {
        self::safe(function () use ($stockRequests) {
            foreach ($stockRequests as $sr) {
                $sr->loadMissing(['user', 'lines']);
                // Umum: admin yang approve · Teknik: manajer yang approve
                $processors = $sr->bidang === 'teknik'
                    ? self::users(['manajer'], $sr->bidang)
                    : self::users(['admin'], $sr->bidang);

                self::send($processors, new AppNotification(
                    'Stok Request baru menunggu approval',
                    self::join([$sr->category, $sr->lines->count().' barang', $sr->user?->name]),
                    'Stok-request',
                    ['kind' => 'sr_pending', 'stock_request_id' => $sr->id],
                ));
            }
        });
    }

    public static function stokRequestDiproses(StockRequest $sr): void
    {
        self::safe(function () use ($sr) {
            self::markRead('sr_pending', 'stock_request_id', [$sr->id]);

            $owner = $sr->user_id && (int) $sr->user_id !== (int) auth()->id() ? User::find($sr->user_id) : null;
            if (! $owner) {
                return;
            }
            $sr->loadMissing('lines');
            $label = $sr->status === 'approved' ? 'disetujui' : 'ditolak';

            $owner->notify(new AppNotification(
                "Stok Request #{$sr->id} {$label}",
                self::join([$sr->category, $sr->lines->count().' barang']),
                'Stok-request',
                ['kind' => 'sr_result', 'stock_request_id' => $sr->id],
            ));
        });
    }

    /* ───────────── Permintaan Barang (form publik) ───────────── */

    public static function permintaanBaru(StuffRequest $r): void
    {
        // User Teknik tidak punya akses ke menu Permintaan Barang
        if ($r->bidang === 'teknik') {
            return;
        }

        self::safe(function () use ($r) {
            $r->loadMissing('lines');
            self::send(self::users(['admin'], $r->bidang), new AppNotification(
                'Permintaan barang baru',
                self::join([$r->requester_name, $r->jabatan, $r->lines->count().' barang']),
                'stuff-requests',
                ['kind' => 'stuff_pending', 'stuff_request_id' => $r->id],
            ));
        });
    }

    public static function permintaanDiproses(StuffRequest $r): void
    {
        self::safe(function () use ($r) {
            self::markRead('stuff_pending', 'stuff_request_id', [$r->id]);

            if (in_array($r->status, ['completed', 'cancel'], true)) {
                self::markRead('stuff_approved', 'stuff_request_id', [$r->id]);

                return;
            }

            if ($r->status === 'approved' && $r->bidang !== 'teknik') {
                $r->loadMissing('lines');
                self::send(self::users(['staf'], $r->bidang), new AppNotification(
                    'Permintaan barang disetujui, siap diserahkan',
                    self::join([$r->requester_name, $r->lines->count().' barang']),
                    'stuff-requests',
                    ['kind' => 'stuff_approved', 'stuff_request_id' => $r->id],
                ));
            }
        });
    }

    /* ───────────── Akun user ───────────── */

    public static function akunBaru(User $account): void
    {
        if ($account->account_status !== 'pending') {
            return;
        }

        self::safe(function () use ($account) {
            self::send(self::users(['manajer'], $account->bidang), new AppNotification(
                'Akun baru menunggu approval',
                self::join([$account->name, $account->username, ucfirst((string) $account->role)]),
                'pending-users?account_status=pending',
                ['kind' => 'user_pending', 'account_id' => $account->id],
            ));
        });
    }

    public static function akunDiproses(User $account): void
    {
        self::safe(fn () => self::markRead('user_pending', 'account_id', [$account->id]));
    }

    /* ───────────── Helper ───────────── */

    /** @return Collection<int, User> */
    private static function users(array $roles, ?string $bidang, bool $exceptActor = true): Collection
    {
        return User::query()
            ->whereIn('role', $roles)
            ->when($bidang, fn ($q) => $q->where('bidang', $bidang))
            ->where('account_status', 'approved')
            ->when($exceptActor && auth()->id(), fn ($q) => $q->where('id', '!=', auth()->id()))
            ->get();
    }

    private static function send(Collection $users, AppNotification $n): void
    {
        if ($users->isNotEmpty()) {
            Notification::send($users, $n);
        }
    }

    /**
     * Tandai notifikasi "tugas" sebagai dibaca untuk semua penerima (tidak mengirim push).
     *
     * @param  array<int, int|string>  $ids
     */
    private static function markRead(string $kind, string $key, array $ids): void
    {
        $ids = array_map('intval', $ids);
        if ($ids === []) {
            return;
        }

        $notifIds = DatabaseNotification::query()
            ->where('type', AppNotification::class)
            ->whereNull('read_at')
            ->where('data', 'like', '%"kind":"'.$kind.'"%')
            ->get(['id', 'data'])
            ->filter(fn (DatabaseNotification $n) => in_array((int) ($n->data[$key] ?? 0), $ids, true))
            ->pluck('id');

        if ($notifIds->isNotEmpty()) {
            DatabaseNotification::query()->whereIn('id', $notifIds)->update(['read_at' => now()]);
        }
    }

    private static function hasUnread(User $user, string $kind, string $key, int $id): bool
    {
        return $user->unreadNotifications()
            ->where('data', 'like', '%"kind":"'.$kind.'"%')
            ->get(['id', 'data'])
            ->contains(fn (DatabaseNotification $n) => (int) ($n->data[$key] ?? 0) === $id);
    }

    private static function txLabel(Transaction $tx): string
    {
        $tx->loadMissing('item');
        $jenis = $tx->bidang === 'teknik'
            ? ($tx->type === 'in' ? 'GR' : 'GI')
            : ($tx->type === 'in' ? 'Masuk' : 'Keluar');
        $tgl = $tx->date ? Carbon::parse($tx->date)->translatedFormat('d M Y') : null;

        return self::join([$jenis, $tx->item?->name, $tx->quantity.' '.($tx->item?->unit ?? ''), $tgl]);
    }

    /** @param  array<int, mixed>  $parts */
    private static function join(array $parts): string
    {
        return collect($parts)->map(fn ($p) => trim((string) $p))->filter()->implode(' • ');
    }

    private static function safe(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            Log::error('InventoryNotifier gagal: '.$e->getMessage());
        }
    }
}
