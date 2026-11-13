<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Notifikasi push di HP diklik → URL membawa ?nl_notif={id}.
 * Tandai notifikasi itu dibaca, lalu redirect ke URL bersih,
 * supaya badge ikon aplikasi langsung berkurang.
 */
class MarkNotificationFromPush
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->query('nl_notif');

        if (! is_string($id) || $id === '' || ! $request->isMethod('GET')) {
            return $next($request);
        }

        if ($request->user()) {
            try {
                $request->user()->unreadNotifications()->where('id', $id)->update(['read_at' => now()]);
            } catch (\Throwable $e) {
                Log::warning('Tandai notifikasi dari push gagal: '.$e->getMessage());
            }
        }

        // Belum login: biarkan alur login normal, parameter dibuang
        return redirect()->to($request->fullUrlWithoutQuery('nl_notif'));
    }
}
