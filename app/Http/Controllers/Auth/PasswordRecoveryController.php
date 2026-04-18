<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendTelegramMessageJob;
use App\Models\TelegramPasswordResetToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PasswordRecoveryController extends Controller
{
    private const TOKEN_TTL_MINUTES = 15;

    public function sendTelegramToken(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = mb_strtolower(trim((string) $request->input('email')));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        // Всегда возвращаем одинаковый ответ, чтобы не раскрывать наличие email.
        $response = response()->json([
            'success' => true,
            'message' => 'Если аккаунт найден и Telegram привязан, одноразовый код отправлен в Telegram.',
        ]);

        if (! $user || ! $user->isTelegramLinked() || ! $user->telegram_id) {
            return $response;
        }

        $plainToken = strtoupper(Str::random(8));

        TelegramPasswordResetToken::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        TelegramPasswordResetToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes(self::TOKEN_TTL_MINUTES),
        ]);

        SendTelegramMessageJob::dispatch(
            (int) $user->telegram_id,
            "Код восстановления пароля: {$plainToken}\n" .
            'Код действует ' . self::TOKEN_TTL_MINUTES . " минут.\n" .
            'Никому не передавайте этот код.'
        )->onQueue('default');

        return $response;
    }

    /**
     * Проверяет Telegram токен, ставит временный пароль и требует смену пароля после входа.
     *
     * @throws ValidationException
     */
    public function resetWithTelegramToken(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string', 'min:6', 'max:64'],
        ]);

        $email = mb_strtolower(trim((string) $request->input('email')));
        $tokenHash = hash('sha256', strtoupper(trim((string) $request->input('token'))));

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $user || ! $user->isTelegramLinked() || ! $user->telegram_id) {
            throw ValidationException::withMessages([
                'token' => ['Неверный или просроченный код восстановления.'],
            ]);
        }

        $resetToken = TelegramPasswordResetToken::query()
            ->where('user_id', $user->id)
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $resetToken) {
            throw ValidationException::withMessages([
                'token' => ['Неверный или просроченный код восстановления.'],
            ]);
        }

        $temporaryPassword = $this->generateTemporaryPassword();

        $resetToken->used_at = now();
        $resetToken->save();

        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
            'remember_token' => Str::random(60),
        ])->save();

        // Отключаем все активные токены, чтобы войти заново по временному паролю.
        $user->tokens()->delete();

        SendTelegramMessageJob::dispatch(
            (int) $user->telegram_id,
            "Ваш временный пароль: {$temporaryPassword}\n" .
            "После входа обязательно смените пароль в форме безопасности."
        )->onQueue('default');

        return response()->json([
            'success' => true,
            'message' => 'Временный пароль отправлен в Telegram. Войдите и смените пароль.',
        ]);
    }

    public function forceChangePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! Hash::check((string) $request->input('current_password'), (string) $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Текущий пароль введён неверно.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make((string) $request->input('password')),
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Пароль успешно изменён.',
        ]);
    }

    private function generateTemporaryPassword(): string
    {
        return Str::upper(Str::random(4)) . '-' . random_int(100000, 999999);
    }
}

