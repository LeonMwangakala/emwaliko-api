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

    /**
     * Get SMS balance from Next SMS API
     * @return int
     */
    public function getBalance(): int
    {
        try {
            if (!$this->baseUrl || !$this->auth) {
                \Log::warning('Next SMS credentials not configured');
                return 0;
            }

            $response = Http::withHeaders([
                'Authorization' => $this->auth,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->get($this->baseUrl . '/api/sms/v1/balance');

            if ($response->successful()) {
                $data = $response->json();
                // Handle the actual API response structure
                $balance = $data['sms_balance'] ?? $data['data']['balance'] ?? 0;
                \Log::info('SMS balance fetched successfully: ' . $balance);
                return (int) $balance;
            }

            \Log::error('Failed to fetch SMS balance. Status: ' . $response->status() . ', Body: ' . $response->body());
            return 0;
        } catch (\Exception $e) {
            \Log::error('Exception while fetching SMS balance: ' . $e->getMessage());
            return 0;
        }
    }
} 