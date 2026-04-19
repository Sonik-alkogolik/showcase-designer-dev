<?php

namespace App\Services;

use App\Models\User;
use App\Support\TelegramHttp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramAvatarService
{
    public function ensureUserAvatar(User $user, ?int $updateId = null): ?string
    {
        try {
            $existing = trim((string) ($user->telegram_avatar_url ?? ''));
            if ($existing !== '') {
                return $existing;
            }

            if (! $user->isTelegramLinked() || ! $user->telegram_id) {
                return null;
            }

            $cooldownKey = "telegram_avatar_sync_cooldown_{$user->id}";
            if (Cache::has($cooldownKey)) {
                return null;
            }

            $avatarUrl = $this->resolveAvatarUrlByTelegramId((int) $user->telegram_id, $updateId);
            if ($avatarUrl === null) {
                Cache::put($cooldownKey, 1, now()->addMinutes(10));
                return null;
            }

            $user->forceFill(['telegram_avatar_url' => $avatarUrl])->save();

            return $avatarUrl;
        } catch (\Throwable $e) {
            Log::warning('Telegram avatar ensure failed', [
                'user_id' => $user->id,
                'update_id' => $updateId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function resolveAvatarUrlByTelegramId(int $telegramId, ?int $updateId = null): ?string
    {
        $token = trim((string) config('telegram.bots.mybot.token'));
        if ($token === '' || $token === 'YOUR-BOT-TOKEN') {
            return null;
        }

        try {
            $photosResponse = TelegramHttp::client()
                ->timeout(8)
                ->get(TelegramHttp::botMethodUrl($token, 'getUserProfilePhotos'), [
                    'user_id' => $telegramId,
                    'limit' => 1,
                ]);

            if (! $photosResponse->ok() || ! (bool) data_get($photosResponse->json(), 'ok', false)) {
                return null;
            }

            $photoVariants = data_get($photosResponse->json(), 'result.photos.0');
            if (! is_array($photoVariants) || count($photoVariants) === 0) {
                return null;
            }

            $largestVariant = end($photoVariants);
            $fileId = is_array($largestVariant) ? (string) ($largestVariant['file_id'] ?? '') : '';
            if ($fileId === '') {
                return null;
            }

            $fileResponse = TelegramHttp::client()
                ->timeout(8)
                ->get(TelegramHttp::botMethodUrl($token, 'getFile'), [
                    'file_id' => $fileId,
                ]);

            if (! $fileResponse->ok() || ! (bool) data_get($fileResponse->json(), 'ok', false)) {
                return null;
            }

            $filePath = trim((string) data_get($fileResponse->json(), 'result.file_path', ''));
            if ($filePath === '') {
                return null;
            }

            return 'https://api.telegram.org/file/bot' . $token . '/' . ltrim($filePath, '/');
        } catch (\Throwable $e) {
            Log::warning('Telegram avatar resolve failed', [
                'update_id' => $updateId,
                'chat_id' => $telegramId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
