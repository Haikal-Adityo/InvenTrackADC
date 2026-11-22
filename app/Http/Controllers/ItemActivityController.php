<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DocumentPrint;
use App\Models\Item;
use App\Models\StockRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Log Aktivitas Master Barang — semua barang dalam satu halaman.
 * Digabung dari: transaksi IN/OUT, permintaan barang, stok request, perubahan master & cetak dokumen.
 */
class ItemActivityController extends Controller
{
    public const TYPES = [
        'in' => ['Barang Masuk', 'bi-box-arrow-in-down', 'success'],
        'out' => ['Barang Keluar', 'bi-box-arrow-up', 'danger'],
        'stuff' => ['Permintaan Barang', 'bi-inbox', 'info'],
        'stock_request' => ['Stok Request', 'bi-cart-check', 'primary'],
        'opname' => ['Stok Opname', 'bi-clipboard-check', 'primary'],
        'master' => ['Master Barang', 'bi-pencil-square', 'secondary'],
        'print' => ['Cetak Dokumen', 'bi-printer', 'warning'],
    ];

    private const STATUS = [
        'pending' => ['Pending', 'warning'],
        'verified' => ['Menunggu Persetujuan', 'primary'],
        'approved' => ['Approved', 'success'],
        'rejected' => ['Rejected', 'danger'],
        'completed' => ['Selesai', 'success'],
        'cancel' => ['Dibatalkan', 'secondary'],
        'submitted' => ['Menunggu Verifikasi', 'warning'],
        'verified_so' => ['Terverifikasi', 'success'],
    ];

    public function index(Request $request)
    {
        $user = auth()->user();
        $filters = $request->validate([
            'item_id' => 'nullable|integer',
            'type' => 'nullable|in:'.implode(',', array_keys(self::TYPES)),
            'user_id' => 'nullable|integer',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'dokumen' => 'nullable|string|max:50',
            'per_page' => 'nullable|integer',
        ]);

        // Buka dari QR PDF Rekap → tampilkan riwayat cetak dokumen tsb
        $document = null;
        if (! empty($filters['dokumen'])) {
            $filters['type'] = 'print';
            $document = DocumentPrint::with('printer')->where('document_number', $filters['dokumen'])->first();
        }

        // Default 3 bulan terakhir supaya halaman tetap ringan; bisa diperluas lewat filter tanggal
        $defaultRange = empty($filters['from']) && empty($filters['to']) && empty($filters['dokumen']) && empty($filters['item_id']);
        if ($defaultRange) {
            $filters['from'] = now()->subMonths(3)->toDateString();
        }

        $items = Item::withTrashed()->visibleFor($user)->orderBy('name')->get(['id', 'name', 'category', 'unit', 'deleted_at']);
        $itemIds = $items->pluck('id');
        $from = ! empty($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : null;
        $to = ! empty($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : null;
        $onlyItem = ! empty($filters['item_id']) ? (int) $filters['item_id'] : null;
        $byUser = ! empty($filters['user_id']) ? (int) $filters['user_id'] : null;
        $want = fn (string $type) => empty($filters['type']) || $filters['type'] === $type;
        $itemScope = fn ($q, string $col = 'item_id') => $onlyItem ? $q->where($col, $onlyItem) : $q->whereIn($col, $itemIds);
        $range = function ($q, string $col) use ($from, $to) {
            if ($from) $q->where($col, '>=', $from);
            if ($to) $q->where($col, '<=', $to);
            return $q;
        };

        $events = collect();
        $userNames = User::query()->pluck('name', 'id');

        // 1) Transaksi IN / OUT
        if ($want('in') || $want('out')) {
            // $userNames dipakai di closure loop di bawah
            $q = Transaction::with(['item' => fn ($q) => $q->withTrashed(), 'user', 'stuffRequest']);
            $itemScope($q);
            $range($q, 'created_at');
            if (! $want('in')) $q->where('type', 'out');
            if (! $want('out')) $q->where('type', 'in');
            if ($byUser) $q->where('user_id', $byUser);

            foreach ($q->get() as $tx) {
                $approver = $tx->approved_by && $tx->status !== 'pending' ? ($userNames[$tx->approved_by] ?? null) : null;
                $events->push($this->event($tx->created_at, $tx->item, $tx->type, [
                    'qty' => ($tx->type === 'in' ? 1 : -1) * (int) $tx->quantity,
                    'status' => $tx->status,
                    'detail' => trim(($tx->price ? 'Rp '.number_format($tx->price, 0, ',', '.').' · ' : '').($tx->description ?? ''), ' ·') ?: '-',
                    'user' => $tx->user?->name ?? 'Input publik',
                    'ref' => $tx->stuffRequest
                        ? 'Permintaan Barang #'.$tx->stuff_request_id
                        : ($approver ? ($tx->status === 'rejected' ? 'Ditolak ' : 'Disetujui ').$approver : 'Transaksi #'.$tx->id),
                    'date_note' => 'Tgl transaksi '.$tx->date?->format('d/m/Y'),
                ]));
            }
        }

        // 2) Permintaan Barang (form publik)
        if ($want('stuff') && ! $byUser) {
            $q = DB::table('stuff_request_items as l')
                ->join('stuff_requests as r', 'r.id', '=', 'l.stuff_request_id')
                ->whereNull('r.deleted_at');
            $itemScope($q, 'l.item_id');
            $range($q, 'r.created_at');

            foreach ($q->get(['l.item_id', 'l.quantity', 'r.id', 'r.requester_name', 'r.jabatan', 'r.notes', 'r.status', 'r.created_at']) as $row) {
                $events->push($this->event(Carbon::parse($row->created_at), $items->firstWhere('id', $row->item_id), 'stuff', [
                    'qty' => (int) $row->quantity,
                    'qty_neutral' => true,
                    'status' => $row->status,
                    'detail' => $row->notes ?: '-',
                    'user' => $row->requester_name.($row->jabatan ? ' ('.$row->jabatan.')' : ''),
                    'ref' => 'Permintaan Barang #'.$row->id,
                ]));
            }
        }

        // 3) Stok Request
        if ($want('stock_request')) {
            $q = DB::table('stock_request_lines as l')
                ->join('stock_requests as r', 'r.id', '=', 'l.stock_request_id')
                ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
                ->whereNull('r.deleted_at');
            $itemScope($q, 'l.item_id');
            $range($q, 'r.created_at');
            if ($byUser) $q->where('r.user_id', $byUser);

            $rows = $q->get(['l.item_id', 'l.quantity', 'l.price', 'r.id', 'r.status', 'r.bidang', 'r.created_at', 'u.name as requester']);
            $numbers = StockRequest::withTrashed()->whereIn('id', $rows->pluck('id')->unique())->get(['id', 'created_at'])
                ->mapWithKeys(fn ($r) => [$r->id => $r->document_number]);

            foreach ($rows as $row) {
                $events->push($this->event(Carbon::parse($row->created_at), $items->firstWhere('id', $row->item_id), 'stock_request', [
                    'qty' => (int) $row->quantity,
                    'qty_neutral' => true,
                    'status' => $row->status === 'pending' && $row->bidang !== 'teknik' ? 'pending_verify' : $row->status,
                    'detail' => (int) $row->price > 0 ? 'Rp '.number_format($row->price, 0, ',', '.').' / satuan' : '-',
                    'user' => $row->requester ?? '-',
                    'ref' => $numbers[$row->id] ?? ('#'.$row->id),
                ]));
            }
        }

        // 4) Stok Opname (per barang yang dihitung)
        if ($want('opname')) {
            $q = DB::table('stock_opname_lines as l')
                ->join('stock_opnames as o', 'o.id', '=', 'l.stock_opname_id')
                ->leftJoin('users as u', 'u.id', '=', 'o.user_id');
            $itemScope($q, 'l.item_id');
            $range($q, 'o.counted_at');
            if ($byUser) $q->where('o.user_id', $byUser);

            $rows = $q->get(['l.item_id', 'l.system_stock', 'l.actual_stock', 'l.difference', 'l.notes', 'o.id', 'o.status', 'o.counted_at', 'u.name as petugas']);
            $soNumbers = \App\Models\StockOpname::whereIn('id', $rows->pluck('id')->unique())->get(['id', 'counted_at', 'created_at'])
                ->mapWithKeys(fn ($o) => [$o->id => $o->document_number]);

            foreach ($rows as $row) {
                $diff = (int) $row->difference;
                $events->push($this->event(Carbon::parse($row->counted_at), $items->firstWhere('id', $row->item_id), 'opname', [
                    'qty' => $diff,
                    'qty_neutral' => $diff === 0,
                    'status' => $row->status === 'verified' ? 'verified_so' : $row->status,
                    'detail' => 'Stok '.$row->system_stock.' → aktual '.$row->actual_stock.($row->notes ? ' · '.$row->notes : ''),
                    'user' => $row->petugas ?? '-',
                    'ref' => $soNumbers[$row->id] ?? ('SO #'.$row->id),
                ]));
            }
        }

        // 5) Master barang & cetak dokumen (tabel activity_logs)
        $wantMaster = $want('master');
        $wantPrint = $want('print') && ! $onlyItem;   // cetak dokumen tidak terkait 1 barang
        if ($wantMaster || $wantPrint) {
            $q = ActivityLog::with('user');
            $range($q, 'created_at');
            if ($byUser) $q->where('user_id', $byUser);
            $q->where(function ($w) use ($wantMaster, $wantPrint, $itemScope, $filters) {
                if ($wantMaster) {
                    $w->orWhere(fn ($m) => $itemScope($m->where('action', 'like', 'item_%')));
                }
                if ($wantPrint) {
                    $w->orWhere(function ($p) use ($filters) {
                        $p->where('action', 'document_printed');
                        if (! empty($filters['dokumen'])) $p->where('document_number', $filters['dokumen']);
                    });
                }
            });

            foreach ($q->get() as $log) {
                $isPrint = $log->action === 'document_printed';
                $events->push($this->event($log->created_at, $isPrint ? null : $items->firstWhere('id', $log->item_id), $isPrint ? 'print' : 'master', [
                    'title' => $isPrint ? ($log->document_number ?? 'Dokumen') : null,
                    'detail' => $log->description ?? '-',
                    'user' => $log->user?->name ?? '-',
                    'ref' => $isPrint ? 'Cetakan ke-'.($log->meta['print_count'] ?? '?') : match ($log->action) {
                        'item_created' => 'Ditambahkan', 'item_deleted' => 'Dihapus', default => 'Diubah',
                    },
                ]));
            }
        }

        $events = $events->sortByDesc(fn ($e) => $e['at']?->timestamp ?? 0)->values();
        $counts = $events->countBy('type');

        $perPage = in_array((int) $request->input('per_page'), [15, 25, 50, 100], true) ? (int) $request->input('per_page') : 15;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginated = new LengthAwarePaginator(
            $events->forPage($page, $perPage)->values(),
            $events->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $users = User::query()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where(fn ($w) => $w->where('bidang', $user->bidang)->orWhere('role', 'superadmin')))
            ->orderBy('name')->get(['id', 'name']);

        return view('items.activity-log', [
            'events' => $paginated,
            'items' => $items,
            'users' => $users,
            'filters' => $filters,
            'counts' => $counts,
            'types' => self::TYPES,
            'document' => $document,
            'defaultRange' => $defaultRange,
            'perPage' => $perPage,
        ]);
    }

    private function event(?Carbon $at, ?Item $item, string $type, array $data): array
    {
        $status = $data['status'] ?? null;
        $statusMeta = $status === 'pending_verify'
            ? ['Menunggu Verifikasi', 'warning']
            : (self::STATUS[$status] ?? null);

        return [
            'at' => $at,
            'type' => $type,
            'type_label' => self::TYPES[$type][0],
            'icon' => self::TYPES[$type][1],
            'color' => self::TYPES[$type][2],
            'title' => $data['title'] ?? ($item?->name ?? 'Barang dihapus'),
            'item_trashed' => (bool) $item?->deleted_at,
            'category' => $item?->category,
            'unit' => $item?->unit,
            'qty' => $data['qty'] ?? null,
            'qty_neutral' => $data['qty_neutral'] ?? false,
            'status_label' => $statusMeta[0] ?? null,
            'status_color' => $statusMeta[1] ?? null,
            'detail' => $data['detail'] ?? '-',
            'date_note' => $data['date_note'] ?? null,
            'user' => $data['user'] ?? '-',
            'ref' => $data['ref'] ?? null,
        ];
    }
}
