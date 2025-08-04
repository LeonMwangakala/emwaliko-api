<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Notification;
use App\Models\Guest;
use App\Models\Event;

class WhatsAppService
{
    private $baseUrl;
    private $accessToken;
    private $phoneNumberId;
    private $webhookVerifyToken;

    public function __construct()
    {
        $this->baseUrl = config('services.whatsapp.base_url');
        $this->accessToken = config('services.whatsapp.access_token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->webhookVerifyToken = config('services.whatsapp.webhook_verify_token');
    }

    public function sendMessage(string $to, string $message): array
    {
        try {
            $url = "{$this->baseUrl}/{$this->phoneNumberId}/messages";
            
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($to),
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->accessToken}",
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message_id' => $data['messages'][0]['id'] ?? null,
                    'response' => $data
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->json(),
                    'status' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function sendBulkMessages(array $messages): array
    {
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($messages as $message) {
            $result = $this->sendMessage($message['to'], $message['text']);
            $results[] = [
                'to' => $message['to'],
                'result' => $result
            ];

            if ($result['success']) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        return [
            'success' => $errorCount === 0,
            'total' => count($messages),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'results' => $results
        ];
    }

    public function verifyWebhookSignature(string $signature, string $body): bool
    {
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $body, $this->webhookVerifyToken);
        return hash_equals($expectedSignature, $signature);
    }

    public function processWebhook(array $payload): array
    {
        try {
            $entry = $payload['entry'][0] ?? null;
            if (!$entry) {
                return ['success' => false, 'error' => 'Invalid webhook payload'];
            }

            $changes = $entry['changes'][0] ?? null;
            if (!$changes || $changes['field'] !== 'messages') {
                return ['success' => false, 'error' => 'Not a message webhook'];
            }

            $value = $changes['value'] ?? null;
            if (!$value) {
                return ['success' => false, 'error' => 'No value in webhook'];
            }

            $messages = $value['messages'] ?? [];
            $statuses = $value['statuses'] ?? [];

            $results = [];

            foreach ($messages as $message) {
                $result = $this->processIncomingMessage($message);
                $results[] = $result;
            }

            foreach ($statuses as $status) {
                $result = $this->processMessageStatus($status);
                $results[] = $result;
            }

            return [
                'success' => true,
                'processed' => count($results),
                'results' => $results
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function processIncomingMessage(array $message): array
    {
        $from = $message['from'] ?? null;
        $text = $message['text']['body'] ?? null;

        if (!$from || !$text) {
            return ['type' => 'message', 'success' => false, 'error' => 'Missing required fields'];
        }

        $guest = Guest::where('phone_number', $this->formatPhoneNumber($from))->first();
        
        if (!$guest) {
            return [
                'type' => 'message',
                'success' => false,
                'error' => 'Guest not found',
                'from' => $from,
                'text' => $text
            ];
        }

        $rsvpResponse = $this->processRSVPResponse($guest, $text);
        
        return [
            'type' => 'message',
            'success' => true,
            'guest_id' => $guest->id,
            'from' => $from,
            'text' => $text,
            'rsvp_response' => $rsvpResponse
        ];
    }

    private function processMessageStatus(array $status): array
    {
        $messageId = $status['id'] ?? null;
        $statusValue = $status['status'] ?? null;

        if (!$messageId || !$statusValue) {
            return ['type' => 'status', 'success' => false, 'error' => 'Missing required fields'];
        }

        $notification = Notification::where('message_id', $messageId)->first();
        
        if ($notification) {
            $notification->update([
                'status' => $this->mapWhatsAppStatus($statusValue),
                'sent_date' => now(),
            ]);
        }

        return [
            'type' => 'status',
            'success' => true,
            'message_id' => $messageId,
            'status' => $statusValue,
            'notification_updated' => $notification ? true : false
        ];
    }

    private function processRSVPResponse(Guest $guest, string $text): array
    {
        $text = strtolower(trim($text));
        
        $yesKeywords = ['yes', 'y', 'accept', 'attending', 'will attend', 'coming', 'ok', 'okay'];
        $noKeywords = ['no', 'n', 'decline', 'not attending', 'not coming', 'sorry', 'cant', "can't"];
        $maybeKeywords = ['maybe', 'm', 'unsure', 'not sure', 'might', 'probably'];

        $response = null;
        $status = null;

        if (in_array($text, $yesKeywords)) {
            $response = 'Yes';
            $status = 'Yes';
        } elseif (in_array($text, $noKeywords)) {
            $response = 'No';
            $status = 'No';
        } elseif (in_array($text, $maybeKeywords)) {
            $response = 'Maybe';
            $status = 'Maybe';
        }

        if ($response) {
            $guest->update(['rsvp_status' => $status]);
            return [
                'success' => true,
                'response' => $response,
                'status' => $status
            ];
        }

        return [
            'success' => false,
            'error' => 'Invalid RSVP response',
            'text' => $text
        ];
    }

    private function mapWhatsAppStatus(string $whatsappStatus): string
    {
        $statusMap = [
            'sent' => 'Sent',
            'delivered' => 'Sent',
            'read' => 'Sent',
            'failed' => 'Not Sent',
            'rejected' => 'Not Sent'
        ];

        return $statusMap[$whatsappStatus] ?? 'Not Sent';
    }

    private function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) === 9 && substr($phone, 0, 1) === '7') {
            $phone = '255' . $phone;
        }
        
        return $phone;
    }
}
