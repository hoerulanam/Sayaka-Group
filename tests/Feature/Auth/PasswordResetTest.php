<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use RuntimeException;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response
            ->assertStatus(200)
            ->assertSee('Reset Password')
            ->assertSee('auth-reset-card', false)
            ->assertSee('Masukkan email akun Anda')
            ->assertSee('Kirim Link Reset Password');
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status', 'Link reset password sudah dikirim. Silakan cek email Anda.');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_gets_safe_generic_reset_response(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'not-registered@example.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Link reset password sudah dikirim. Silakan cek email Anda.');

        Notification::assertNothingSent();
    }

    public function test_reset_password_request_is_throttled_after_first_link(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status', 'Link reset password sudah dikirim. Silakan cek email Anda.');

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasErrors([
                'email' => 'Link reset password sudah pernah dikirim. Tunggu 60 detik sebelum meminta link baru.',
            ]);
    }

    public function test_reset_password_link_handles_mail_transport_errors(): void
    {
        User::factory()->create(['email' => 'customer@example.com']);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->andThrow(new RuntimeException('SMTP connection failed.'));

        $this->post('/forgot-password', ['email' => 'customer@example.com'])
            ->assertSessionHasErrors([
                'email' => 'Email reset password belum bisa dikirim. Periksa konfigurasi Mailtrap lalu coba lagi.',
            ]);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response
                ->assertStatus(200)
                ->assertSee('Buat Password Baru')
                ->assertSee('auth-reset-card', false)
                ->assertSee('Simpan Password Baru');

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'))
                ->assertSessionHas('status', 'Password berhasil direset. Silakan login dengan password baru.');

            $this->assertTrue(Hash::check('password', $user->fresh()->password));

            return true;
        });
    }

    public function test_reset_password_email_uses_sayaka_copy_and_app_url(): void
    {
        config(['app.url' => 'https://sayaka.test']);

        $user = User::factory()->create(['email' => 'customer@example.com']);

        $mail = (new ResetPassword('test-token'))->toMail($user);

        $this->assertSame('Reset Password Akun SAYAKA GROUP', $mail->subject);
        $this->assertSame('Reset Password', $mail->actionText);
        $this->assertStringStartsWith('https://sayaka.test/reset-password/test-token', $mail->actionUrl);
        $this->assertStringContainsString('email=customer%40example.com', $mail->actionUrl);
        $this->assertContains('Kami menerima permintaan reset password untuk akun SAYAKA GROUP Anda.', $mail->introLines);
    }
}
