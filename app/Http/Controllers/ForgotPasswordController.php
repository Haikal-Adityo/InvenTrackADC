<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::broker()->sendResetLink($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $status === Password::RESET_LINK_SENT
                ? response()->json(['success' => true, 'message' => $this->statusMessage($status)])
                : response()->json([
                    'success' => false,
                    'message' => $this->statusMessage($status),
                    'errors' => ['email' => [$this->statusMessage($status)]],
                ], 422);
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', $this->statusMessage($status))
            : back()->withErrors(['email' => $this->statusMessage($status)])->onlyInput('email');
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $request->merge([
            'password_confirmation' => $request->input('password'),
        ]);

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker()->reset(
            $validated,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Password berhasil diubah. Silakan login dengan password baru.')
            : back()->withErrors(['email' => $this->statusMessage($status)])->withInput($request->only('email'));
    }

    private function statusMessage(string $status): string
    {
        return match ($status) {
            Password::RESET_LINK_SENT => 'Link reset password sudah dikirim ke email Anda.',
            Password::PASSWORD_RESET => 'Password berhasil diubah.',
            Password::INVALID_USER => 'Email tidak ditemukan.',
            Password::INVALID_TOKEN => 'Link reset password tidak valid atau sudah kedaluwarsa.',
            Password::RESET_THROTTLED => 'Terlalu banyak permintaan. Silakan coba lagi nanti.',
            default => 'Permintaan reset password tidak dapat diproses.',
        };
    }
}
