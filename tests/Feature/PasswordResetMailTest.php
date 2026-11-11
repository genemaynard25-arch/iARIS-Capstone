<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class PasswordResetMailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    }

    public function test_smtp_failure_returns_a_safe_error_and_allows_an_immediate_retry(): void
    {
        $user = User::factory()->create();
        Notification::shouldReceive('send')->once()->andThrow(new TransportException('535 BadCredentials private SMTP detail'));
        $this->from('/login')->followingRedirects()->post('/forgot-password', ['email' => $user->email])
            ->assertOk()->assertSee('We could not send the password reset email.')->assertDontSee('BadCredentials');
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        Notification::fake();
        $this->postJson('/forgot-password', ['email' => $user->email])->assertOk();
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_smtp_failure_returns_validation_errors_for_json_clients(): void
    {
        $user = User::factory()->create();
        Notification::shouldReceive('send')->once()->andThrow(new TransportException('535 BadCredentials'));
        $this->postJson('/forgot-password', ['email' => $user->email])
            ->assertUnprocessable()->assertJsonValidationErrors('email')->assertDontSee('BadCredentials');
    }

    public function test_successful_delivery_keeps_a_usable_token_and_throttles_repeated_requests(): void
    {
        $user = User::factory()->create();
        Notification::fake();
        $this->postJson('/forgot-password', ['email' => $user->email])->assertOk();
        Notification::assertSentTo($user, ResetPassword::class, fn ($notification) => Password::broker()->tokenExists($user, $notification->token));
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
        $this->postJson('/forgot-password', ['email' => $user->email])->assertUnprocessable();
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_unknown_or_invalid_email_does_not_create_a_token(): void
    {
        Notification::fake();
        $this->postJson('/forgot-password', ['email' => 'unknown@example.test'])->assertUnprocessable();
        $this->postJson('/forgot-password', ['email' => 'invalid'])->assertUnprocessable();
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
        Notification::assertNothingSent();
    }
}
