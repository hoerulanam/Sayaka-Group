<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );
        } catch (Throwable $exception) {
            Log::warning('Password reset email could not be sent.', [
                'email' => $request->input('email'),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Email reset password belum bisa dikirim. Periksa konfigurasi Mailtrap lalu coba lagi.',
                ]);
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Link reset password sudah pernah dikirim. Tunggu 60 detik sebelum meminta link baru.',
                ]);
        }

        if ($status === Password::RESET_LINK_SENT || $status === Password::INVALID_USER) {
            return back()->with('status', 'Link reset password sudah dikirim. Silakan cek email Anda.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => 'Reset password belum bisa diproses. Silakan coba lagi beberapa saat.',
            ]);
    }
}
