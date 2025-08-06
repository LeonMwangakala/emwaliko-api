<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        // Try both dot and underscore formats
        $mode = $request->get('hub.mode') ?: $request->get('hub_mode');
        $token = $request->get('hub.verify_token') ?: $request->get('hub_verify_token');
        $challenge = $request->get('hub.challenge') ?: $request->get('hub_challenge');

        $webhookVerifyToken = config('services.whatsapp.webhook_verify_token');

        Log::info('Webhook verification attempt', [
            'mode' => $mode,
            'token_received' => $token,
            'token_expected' => $webhookVerifyToken,
            'challenge' => $challenge
        ]);

        if ($mode === 'subscribe' && $token === $webhookVerifyToken) {
            Log::info('WhatsApp webhook verified successfully');
            return response()->json($challenge, 200);
        }

        Log::error('WhatsApp webhook verification failed', [
            'mode' => $mode,
            'token' => $token,
            'expected_token' => $webhookVerifyToken
        ]);

        return response()->json(['error' => 'Verification failed'], 403);
    }

    public function handle(Request $request): JsonResponse
    {
        try {
            $signature = $request->header('X-Hub-Signature-256');
            $body = $request->getContent();

            $whatsappService = new WhatsAppService();

            // Verify webhook signature
            if (!$whatsappService->verifyWebhookSignature($signature, $body)) {
                Log::error('WhatsApp webhook signature verification failed', [
                    'signature' => $signature,
                    'body_length' => strlen($body)
                ]);
                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $payload = $request->json()->all();
            
            // Process the webhook
            $result = $whatsappService->processWebhook($payload);

            if ($result['success']) {
                Log::info('WhatsApp webhook processed successfully', [
                    'processed' => $result['processed'],
                    'results' => $result['results']
                ]);

                return response()->json([
                    'success' => true,
                    'processed' => $result['processed']
                ]);
            } else {
                Log::error('WhatsApp webhook processing failed', [
                    'error' => $result['error'],
                    'payload' => $payload
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error'
            ], 500);
        }
    }
}
