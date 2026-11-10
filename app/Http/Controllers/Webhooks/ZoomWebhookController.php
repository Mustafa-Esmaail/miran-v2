<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\ZoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZoomWebhookController extends Controller
{
    public function __invoke(Request $request, ZoomService $zoom): JsonResponse
    {
        $payload = $request->all();

        Log::channel('zoom')->info('Zoom webhook request received', [
            'event' => $payload['event'] ?? 'unknown',
            'ip' => $request->ip(),
        ]);

        if (($payload['event'] ?? '') === 'endpoint.url_validation') {
            $plainToken = (string) data_get($payload, 'payload.plainToken', '');

            Log::channel('zoom')->info('Zoom webhook endpoint validation processed');

            return response()->json([
                'plainToken' => $plainToken,
                'encryptedToken' => $zoom->validateEndpoint($plainToken),
            ]);
        }

        $zoom->handleWebhook($payload);

        Log::channel('zoom')->info('Zoom webhook processed successfully');

        return response()->json(['received' => true]);
    }
}
