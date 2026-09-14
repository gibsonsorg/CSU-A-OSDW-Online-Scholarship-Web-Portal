<?php

namespace App\Services;

use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiditVerificationService
{
    public function baseUrl(): string
    {
        return rtrim((string) config('services.didit.base_url', 'https://verification.didit.me/v3/'), '/') . '/';
    }

    public function callbackUrl(): string
    {
        return url('/webhooks/didit');
    }

    public function createSession(User $user, Scholarship $scholarship): array
    {
        $apiKey = (string) config('services.didit.api_key');
        $workflowId = (string) config('services.didit.workflow_id');

        if ($apiKey === '' || $workflowId === '') {
            throw new \RuntimeException('Didit API key or workflow ID is not configured.');
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->acceptJson()->post($this->baseUrl() . 'session/', [
            'workflow_id' => $workflowId,
            'vendor_data' => (string) $user->id,
            'callback' => $this->callbackUrl(),
        ]);

        if ($response->failed()) {
            $body = $response->json();
            Log::error('Didit session creation failed', [
                'status' => $response->status(),
                'body' => is_array($body) ? $body : ['message' => (string) $body],
            ]);

            throw new \RuntimeException('Unable to create the Didit verification session right now. Please try again.');
        }

        $payload = $response->json() ?? [];
        $sessionId = $payload['session_id'] ?? $payload['id'] ?? null;
        $sessionUrl = $payload['url'] ?? $payload['verification_url'] ?? null;

        if (empty($sessionId) || empty($sessionUrl)) {
            Log::error('Didit session response missing required data', ['payload' => $payload]);
            throw new \RuntimeException('Didit returned an incomplete verification response. Please try again.');
        }

        return [
            'session_id' => $sessionId,
            'url' => $sessionUrl,
            'raw' => $payload,
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = (string) config('services.didit.webhook_secret');
        if ($secret === '') {
            return false;
        }

        $signature = (string) $request->header('X-Signature-V2', $request->header('x-signature-v2', ''));
        $payload = (string) $request->getContent();
        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
