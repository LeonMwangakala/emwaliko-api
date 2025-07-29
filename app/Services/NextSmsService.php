<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NextSmsService
{
    protected $baseUrl;
    protected $auth;

    public function __construct()
    {
        $this->baseUrl = config('services.nextsms.base_url');
        $this->auth = config('services.nextsms.auth');
    }

    /**
     * Send bulk SMS messages to multiple destinations.
     * @param array $messages
     * @param string|null $reference Reference string for tracking (should be saved with each notification)
     * @return array|null
     */
    public function sendBulk(array $messages, $reference = null)
    {
        $payload = [
            'messages' => $messages,
            'reference' => $reference ?? uniqid('ref_'),
        ];

        $response = Http::withHeaders([
            'Authorization' => $this->auth,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($this->baseUrl . '/api/sms/v1/text/multi', $payload);

        if ($response->successful()) {
            return $response->json();
        } else {
            $errorBody = $response->body();
            $errorData = json_decode($errorBody, true);
            
            // Check if it's a duplicate request error (which means SMS was actually sent)
            if ($response->status() === 422 && 
                isset($errorData['error']) && 
                strpos($errorData['error'], 'Duplicate request sent within past 48 hours') !== false) {
                
                // Return a mock success response for duplicate requests
                // This allows us to mark notifications as sent since the SMS was actually delivered
                $mockMessages = [];
                foreach ($payload['messages'] as $message) {
                    foreach ($message['to'] as $phone) {
                        $mockMessages[] = [
                            'to' => $phone,
                            'status' => [
                                'groupId' => 18,
                                'groupName' => 'PENDING',
                                'id' => 51,
                                'name' => 'PENDING_ENROUTE',
                                'description' => 'Message sent to next instance (duplicate)'
                            ],
                            'messageId' => 'DUPLICATE_' . time() . '_' . rand(1000, 9999),
                            'smsCount' => 1,
                            'message' => $message['text']
                        ];
                    }
                }
                
                return ['messages' => $mockMessages];
            }
        }
        return null;
    }
} 