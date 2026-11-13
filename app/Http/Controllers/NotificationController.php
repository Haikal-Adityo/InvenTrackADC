<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * NextLog tidak punya panel lonceng, jadi notifikasi dianggap "sudah dilihat"
 * saat user membuka halaman tujuan notifikasi tersebut.
 */
class NotificationController extends Controller
{
    public function readPath(Request $request): JsonResponse
    {
        $user = $request->user();
        $path = self::normalizePath((string) $request->input('href', ''), $request->getBaseUrl());

        $ids = [];
        if ($path !== null) {
            $ids = $user->unreadNotifications()
                ->get(['id', 'data'])
                ->filter(fn (DatabaseNotification $n) => self::normalizePath((string) ($n->data['url'] ?? ''), '') === $path)
                ->pluck('id')
                ->all();

            if ($ids !== []) {
                $user->unreadNotifications()->whereIn('id', $ids)->update(['read_at' => now()]);
            }
        }

        return response()->json([
            'ok' => true,
            'ids' => array_values($ids),
            'count' => $user->unreadNotifications()->count(),
        ]);
    }

    /** "https://host/base/Stok-request?x=1#a" atau "Stok-request?x=1" → "stok-request". */
    private static function normalizePath(string $href, string $basePath): ?string
    {
        $path = parse_url($href, PHP_URL_PATH);
        if (! is_string($path)) {
            return null;
        }
        $path = '/'.ltrim($path, '/');
        $basePath = rtrim($basePath, '/');
        if ($basePath !== '' && str_starts_with(strtolower($path), strtolower($basePath.'/'))) {
            $path = substr($path, strlen($basePath));
        }
        $path = strtolower(trim($path, '/'));

        return $path === '' ? null : $path;
    }
}
