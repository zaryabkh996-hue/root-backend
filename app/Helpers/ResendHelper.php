<?php

namespace App\Helpers;

class ResendHelper
{

    public static function sendEmail($email, $subject, $htmlContent, $fromName = null)
    {
        try {
            $apiKey = env('RESEND_API_KEY');
            $fromEmail = env('RESEND_FROM_EMAIL', 'noreply@ourroots.africa');
            
            if (!$apiKey) {
                throw new \Exception('Resend API key not configured in .env');
            }

            $ch = curl_init();

            $fromFormatted = $fromName 
                ? "{$fromName} <{$fromEmail}>" 
                : (env('APP_NAME', 'Amen Our Roots Africa') . " <{$fromEmail}>");

            $payload = json_encode([
                'from' => $fromFormatted,
                'to' => [$email],
                'subject' => $subject,
                'html' => $htmlContent,
                'text' => strip_tags($htmlContent)
            ]);

            curl_setopt($ch, CURLOPT_URL, "https://api.resend.com/emails");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $headers = [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json; charset=utf-8'
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $server_output = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_errno = curl_errno($ch);
            $curl_error = curl_error($ch);

            if ($curl_errno) {
                curl_close($ch);
                throw new \Exception("cURL Error ({$curl_errno}): {$curl_error}");
            }

            curl_close($ch);

            $response = json_decode($server_output, true);

            if (($http_code === 200 || $http_code === 201) && isset($response['id'])) {
                return true;
            } else {
                $errorMsg = $response['message'] ?? 'Unknown error';
                throw new \Exception("Resend Error: {$errorMsg}");
            }

        } catch (\Exception $e) {
            throw $e;
        }
    }
}
