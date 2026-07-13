<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppHelper
{
    /**
     * Send a WhatsApp crisis warning notification.
     */
    public static function sendCrisisAlert(string $recipientPhone, string $clientName, string $conversationId): bool
    {
        Log::warning("CRISIS ALERT: Dispatching WhatsApp alert to {$recipientPhone} for client {$clientName}.", [
            'conversation_id' => $conversationId,
            'recipient'       => $recipientPhone,
            'client'          => $clientName,
        ]);

        $apiUrl = env('WHATSAPP_API_URL');
        $token = env('WHATSAPP_API_TOKEN');

        if ($apiUrl && $token) {
            try {
                $res = Http::withToken($token)->post($apiUrl, [
                    'to'      => $recipientPhone,
                    'message' => "CRISIS WARNING: Your client {$clientName} has triggered an SOS crisis alert. Please check your dashboard or contact them immediately: " . url("/custodian/dashboard"),
                ]);
                return $res->successful();
            } catch (\Exception $e) {
                Log::error("Failed to send WhatsApp crisis notification: " . $e->getMessage());
            }
        }

        return true;
    }
}
