<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonntaWhatsAppService
{
    private string $apiUrl = 'https://api.fonnte.com/send';
    private string $apiToken;

    public function __construct()
    {
        $this->apiToken = config('services.fonnte.token', '');
    }

    /**
     * Send WhatsApp message via Fonnte API
     */
    public function send(string $phoneNumber, string $message): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Fonnte API token is not configured',
                'phone' => $phoneNumber,
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
            ])->post($this->apiUrl, [
                'target' => $this->formatPhoneNumber($phoneNumber),
                'message' => $message,
                'countryCode' => '62',
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp notification sent', [
                    'phone' => $phoneNumber,
                    'response' => $response->json(),
                ]);

                return [
                    'success' => true,
                    'phone' => $phoneNumber,
                    'data' => $response->json(),
                ];
            }

            Log::warning('WhatsApp notification failed', [
                'phone' => $phoneNumber,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to send message',
                'phone' => $phoneNumber,
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp notification exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
            ];
        }
    }

    /**
     * Send to multiple recipients
     */
    public function sendBulk(array $phoneNumbers, string $message): array
    {
        $results = [];

        foreach ($phoneNumbers as $phone) {
            $results[] = $this->send($phone, $message);
        }

        return $results;
    }

    /**
     * Format phone number for Fonnte API
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phoneNumber);

        if (str_starts_with($phone, '+62')) {
            return $phone;
        }

        if (str_starts_with($phone, '62')) {
            return '+' . $phone;
        }

        if (str_starts_with($phone, '0')) {
            return '+62' . substr($phone, 1);
        }

        return '+62' . $phone;
    }

    /**
     * Check if Fonnte is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiToken);
    }

    /**
     * Get API token
     */
    public function getApiToken(): string
    {
        return $this->apiToken;
    }
}
