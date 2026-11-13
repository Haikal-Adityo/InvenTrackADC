<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless(in_array($request->user()?->role, config('webpush.roles', []), true), 403);

        $data = $request->validate([
            'endpoint' => 'required|url|max:2000',
            'keys.p256dh' => 'required|string|max:255',
            'keys.auth' => 'required|string|max:255',
            'contentEncoding' => 'nullable|string|max:20',
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => PushSubscription::hashEndpoint($data['endpoint'])],
            [
                'user_id' => $request->user()->id,
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Apakah perangkat ini terdaftar untuk user yang sedang login?
     * Endpoint milik user lain (HP dipakai bergantian) → langganan lama dihapus
     * supaya notifikasi akun sebelumnya tidak muncul lagi di perangkat ini.
     */
    public function check(Request $request): JsonResponse
    {
        $endpoint = (string) $request->input('endpoint', '');
        $sub = $endpoint === '' ? null : PushSubscription::query()
            ->where('endpoint_hash', PushSubscription::hashEndpoint($endpoint))
            ->first();

        if ($sub && (int) $sub->user_id !== (int) $request->user()->id) {
            $sub->delete();
            $sub = null;
        }

        // Sesi bisa berganti (mis. login ulang lewat "ingat saya") → ikat ke sesi sekarang,
        // supaya logout manual berikutnya tetap menghapus langganan perangkat ini.
        if ($sub && $request->hasSession() && $sub->session_id !== $request->session()->getId()) {
            $sub->forceFill(['session_id' => $request->session()->getId()])->save();
        }

        return response()->json(['subscribed' => (bool) $sub]);
    }

    /** Jumlah notifikasi belum dibaca — untuk sinkron badge ikon aplikasi. */
    public function badge(Request $request): JsonResponse
    {
        return response()->json(['count' => $request->user()->unreadNotifications()->count()]);
    }
}
