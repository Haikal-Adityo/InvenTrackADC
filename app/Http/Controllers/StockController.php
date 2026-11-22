<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockRequestLine;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $isSuperAdmin = auth()->check() && auth()->user()->isSuperAdmin();
        $saBidang = $this->superAdminBidangContext($request);
        $viewUser = $saBidang ? $this->createBidangProxy($saBidang) : auth()->user();

        $query = Item::query()->visibleFor($viewUser);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('component', 'like', "%{$search}%")
                    ->orWhere('no_normalisasi', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where($viewUser?->isTeknik() ? 'component' : 'category', $request->category);
        }

        $stockRequestItems = $this->stockRequestTriggerItems($viewUser);
        $pendingStockRequestItemIds = $this->pendingStockRequestItemIds();
        $pricing = \App\Support\StockRequestPricing::forItems($stockRequestItems->pluck('id'));
        $stockRequestItems = $stockRequestItems->map(function ($item) use ($pendingStockRequestItemIds, $pricing) {
            $item['has_pending_request'] = in_array($item['id'], $pendingStockRequestItemIds, true);
            $item['pricing'] = $pricing[$item['id']] ?? null;

            return $item;
        });
        $requestOrderCount = $stockRequestItems->where('stock_status', 'request_order')->count();
        $outOfStockCount = $stockRequestItems->where('stock_status', 'out_of_stock')->count();

        if ($request->filled('stock_status')) {
            $status = $request->stock_status;
            $items = $query->get()->filter(function ($item) use ($status) {
                $currentStock = $item->current_stock;
                $minStock = $item->min_stock;

                return match ($status) {
                    'safe', 'ready' => $currentStock > $minStock,
                    'in_stock' => $currentStock > $minStock,
                    'request_stock', 'request_order' => $currentStock > 0 && $currentStock <= $minStock,
                    'out_of_stock' => $currentStock <= 0,
                    'critical' => $currentStock < $minStock,
                    'low', 'low_stock' => $currentStock == $minStock,
                    default => true,
                };
            });
            $categoryColumn = $viewUser?->isTeknik() ? 'component' : 'category';
            $categories = Item::visibleFor($viewUser)->select($categoryColumn)->whereNotNull($categoryColumn)->distinct()->orderBy($categoryColumn)->pluck($categoryColumn);

            return view('stock.index', [
                'items' => $items,
                'categories' => $categories,
                'paginated' => false,
                'stockRequestItems' => $stockRequestItems,
                'requestOrderCount' => $requestOrderCount,
                'outOfStockCount' => $outOfStockCount,
                'isSuperAdmin' => $isSuperAdmin,
                'saBidang' => $saBidang,
                'isTeknik' => $viewUser?->isTeknik(),
            ]);
        }

        $items = $query->orderBy('name')->paginate(15)->withQueryString();
        $categoryColumn = $viewUser?->isTeknik() ? 'component' : 'category';
        $categories = Item::visibleFor($viewUser)->select($categoryColumn)->whereNotNull($categoryColumn)->distinct()->orderBy($categoryColumn)->pluck($categoryColumn);

        return view('stock.index', compact(
            'items',
            'categories',
            'stockRequestItems',
            'requestOrderCount',
            'outOfStockCount'
        ) + [
            'paginated' => true,
            'isSuperAdmin' => $isSuperAdmin,
            'saBidang' => $saBidang,
            'isTeknik' => $viewUser?->isTeknik(),
        ]);
    }

    private function stockRequestTriggerItems($viewUser)
    {
        return Item::visibleFor($viewUser)
            ->orderBy('name')
            ->get()
            ->map(function (Item $item) {
                $stock = $item->current_stock;

                if ($stock <= 0) {
                    $status = 'out_of_stock';
                } elseif ($stock <= $item->min_stock) {
                    $status = 'request_order';
                } else {
                    return null;
                }

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'no_normalisasi' => $item->no_normalisasi,
                    'category' => $item->category,
                    'component' => $item->component,
                    'lokasi' => $item->lokasi,
                    'volume' => $item->volume,
                    'ship_unloader' => $item->stock_ship_unloader_label,
                    'unit' => $item->unit,
                    'current_stock' => $stock,
                    'min_stock' => $item->min_stock,
                    'stock_status' => $status,
                ];
            })
            ->filter()
            ->values();
    }

    private function pendingStockRequestItemIds(): array
    {
        if (!auth()->check() || !auth()->user()->isStaff()) {
            return [];
        }

        return StockRequestLine::whereHas('stockRequest', function ($query) {
                $query->where('user_id', auth()->id())
                    ->whereIn('status', ['pending', 'verified']);
            })
            ->pluck('item_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
