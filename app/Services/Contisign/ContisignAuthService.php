<?php

namespace App\Services\Contisign;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/*
|--------------------------------------------------------------------------
| ContisignAuthService
|--------------------------------------------------------------------------
| Owns the Contisign bearer token: sign in, cache it with its expiry, and
| transparently refresh when it's expired or close to expiring. Callers just
| ask for token() and never deal with the lifecycle.
|
| Token validity:
|   - Uses the `expiresIn` timestamp returned by Contisign when present.
|   - Falls back to config('contisign.token.fallback_ttl') (1h) otherwise.
|   - Refreshes early within `refresh_skew` seconds of expiry.
*/

class ContisignAuthService
{
    private string $base;
    private array $cfg;

    public function __construct()
    {
        $this->base = rtrim((string) config('contisign.base_url'), '/');
        $this->cfg  = config('contisign');
    }

    /** Return a valid token, signing in or refreshing as needed. */
    public function token(): string
    {
        $cached = Cache::get($this->cacheKey());

        if ($cached && isset($cached['token'], $cached['expires_at'])) {
            if (! $this->isNearExpiry($cached['expires_at'])) {
                return $cached['token'];
            }
            // Near expiry: try a refresh, fall back to a fresh sign-in.
            try {
                return $this->refresh($cached['token']);
            } catch (\Throwable $e) {
                Log::warning('Contisign token refresh failed, re-authenticating.', ['error' => $e->getMessage()]);
            }
        }

        return $this->signIn();
    }

    /** Force a fresh sign-in (ignores cache). */
    public function signIn(): string
    {
        $url = $this->base . $this->cfg['endpoints']['signin'];

        $response = $this->client()->post($url, [
            'email'    => $this->cfg['credentials']['email'],
            'password' => $this->cfg['credentials']['password'],
        ]);

        if (! $response->successful()) {
            Log::error('Contisign sign-in failed.', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('Contisign authentication failed.');
        }

        return $this->storeFromResponse($response->json());
    }

    /** Refresh using the current token in the Authorization header. */
    public function refresh(string $currentToken): string
    {
        $url = $this->base . $this->cfg['endpoints']['refresh'];

        $response = $this->client()
            ->withHeaders(['Authorization' => $currentToken])
            ->post($url);

        if (! $response->successful()) {
            throw new RuntimeException('Contisign token refresh failed.');
        }

        return $this->storeFromResponse($response->json());
    }

    /** Invalidate the cached token (e.g. after a 401 from an API call). */
    public function forget(): void
    {
        Cache::forget($this->cacheKey());
    }

    /* ----------------------------------------------------------------
     | Internals
     | ---------------------------------------------------------------- */

    private function storeFromResponse(array $data): string
    {
        $token = $data['token'] ?? null;
        if (! $token) {
            Log::debug('Contisign response missing token.', ['response' => $data]);
            throw new RuntimeException('Contisign response did not include a token.');
        }

        $expiresAt = $this->resolveExpiry($data['expiresIn'] ?? null);

        Cache::put($this->cacheKey(), [
            'token'      => $token,
            'expires_at' => $expiresAt,
        ], $expiresAt); // cache entry itself expires with the token

        return $token;
    }

    /** Resolve an absolute expiry unix timestamp from the API value or fallback. */
    private function resolveExpiry(mixed $expiresIn): int
    {
        if (is_string($expiresIn) && $expiresIn !== '') {
            try {
                return strtotime($expiresIn) ?: $this->fallbackExpiry();
            } catch (\Throwable) {
                return $this->fallbackExpiry();
            }
        }
        return $this->fallbackExpiry();
    }

    private function fallbackExpiry(): int
    {
        return time() + (int) $this->cfg['token']['fallback_ttl'];
    }

    private function isNearExpiry(int $expiresAt): bool
    {
        return time() >= ($expiresAt - (int) $this->cfg['token']['refresh_skew']);
    }

    private function cacheKey(): string
    {
        return (string) $this->cfg['token']['cache_key'];
    }

    private function client()
    {
        return Http::timeout((int) $this->cfg['http']['timeout'])
            ->connectTimeout((int) $this->cfg['http']['connect_timeout'])
            ->acceptJson();
    }
}
