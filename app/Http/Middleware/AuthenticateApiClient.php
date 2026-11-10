<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use App\Services\Api\ApiKeyService;
use Closure;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Implements AuthenticatesRequests only so the router's middleware priority
 * runs it before "throttle", which rate-limits by the resolved client.
 */
class AuthenticateApiClient implements AuthenticatesRequests
{
    public const HEADER = 'X-API-Key';

    public function __construct(private readonly ApiKeyService $apiKeys) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        $plainKey = $request->header(self::HEADER) ?: $request->bearerToken();

        if (blank($plainKey)) {
            return $this->reject('missing_api_key', 'API key is missing.', Response::HTTP_UNAUTHORIZED);
        }

        $client = $this->apiKeys->findByPlainKey($plainKey);

        if ($client === null) {
            return $this->reject('invalid_api_key', 'API key is invalid.', Response::HTTP_UNAUTHORIZED);
        }

        if ($client->isRevoked()) {
            return $this->reject('api_key_revoked', 'API key has been revoked.', Response::HTTP_UNAUTHORIZED, $client, $request);
        }

        if ($client->isExpired()) {
            return $this->reject('api_key_expired', 'API key has expired.', Response::HTTP_UNAUTHORIZED, $client, $request);
        }

        if (! $client->allowsIp($request->ip())) {
            return $this->reject('ip_not_allowed', 'Requests from this IP are not allowed.', Response::HTTP_FORBIDDEN, $client, $request);
        }

        $request->attributes->set('api_client', $client);
        $this->touch($client, $request);

        $response = $next($request);

        Log::channel('api')->info('api.request', [
            'client_id' => $client->id,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }

    /**
     * Record usage at most once a minute to avoid a write on every request.
     */
    private function touch(ApiClient $client, Request $request): void
    {
        if ($client->last_used_at !== null && $client->last_used_at->gt(now()->subMinute())) {
            return;
        }

        $client->forceFill([
            'last_used_at' => now(),
            'last_used_ip' => $request->ip(),
        ])->saveQuietly();
    }

    private function reject(string $code, string $message, int $status, ?ApiClient $client = null, ?Request $request = null): JsonResponse
    {
        if ($client !== null) {
            Log::channel('api')->warning('api.rejected', [
                'client_id' => $client->id,
                'code' => $code,
                'ip' => $request?->ip(),
            ]);
        }

        return response()->json(['message' => $message, 'code' => $code], $status);
    }
}
