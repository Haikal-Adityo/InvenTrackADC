<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public const INVALID_LOGIN_MESSAGE = 'Username atau password salah.';

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return redirect('/')->with('openLogin', true);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $isAjax = $request->ajax() || $request->wantsJson();

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if ($user->account_status !== 'approved') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $statusMsg = $user->account_status === 'pending'
                    ? 'Akun Anda masih menunggu persetujuan dari Manager.'
                    : 'Akun Anda ditolak. Silakan hubungi Administrator.';

                if ($isAjax) {
                    return response()->json(['success' => false, 'message' => $statusMsg], 422);
                }

                return back()->withErrors(['username' => $statusMsg])->onlyInput('username');
            }

            $user->forceFill(['last_login_at' => now()])->save();
            $request->session()->regenerate();

            $redirect = $user->isStaff()
                ? route('transactions.index')
                : route('dashboard');

            if ($isAjax) {
                return response()->json(['success' => true, 'redirect' => $redirect]);
            }

            $response = $user->isTeknik()
                ? redirect($redirect)
                : redirect()->intended($redirect);

            return $response->with('success', 'Selamat datang, ' . $user->name . '!');
        }

        if ($isAjax) {
            return response()->json([
                'success' => false,
                'message' => self::INVALID_LOGIN_MESSAGE,
            ], 422);
        }

        return back()->withErrors([
            'username' => self::INVALID_LOGIN_MESSAGE,
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        // Logout manual = berhenti menerima push di perangkat/sesi ini (perangkat lain tetap aktif).
        // Login lagi di perangkat ini → otomatis terdaftar ulang tanpa popup.
        if ($userId = $request->user()?->id) {
            try {
                \App\Models\PushSubscription::query()
                    ->where('user_id', $userId)
                    ->where('session_id', $request->session()->getId())
                    ->delete();
            } catch (\Throwable $e) {
                // Logout tidak boleh gagal hanya karena urusan push (mis. tabel belum dimigrasi)
                \Illuminate\Support\Facades\Log::warning('Hapus langganan push saat logout gagal: '.$e->getMessage());
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Berhasil logout.');
    }
}
