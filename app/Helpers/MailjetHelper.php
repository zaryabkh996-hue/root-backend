<?php

namespace App\Helpers;

class MailjetHelper
{
    /**
     * Send email via Mailjet API (Temporary - until SMTP works on Railway)
     * TODO: Switch back to SMTP when deploying to production server
     */
    public static function sendEmail($email, $subject, $htmlContent, $fromName = null)
    {
        try {
            $apiKey = env('MAILJET_PUBLIC_KEY');
            $apiSecret = env('MAILJET_SECRET_KEY');
            $fromEmail = env('MAILJET_FROM_EMAIL', 'noreply@amenourroots.com');
            
            \Log::info('🔵 [MAILJET] Email sending started', [
                'recipient' => $email,
                'subject' => $subject,
                'from_email' => $fromEmail,
                'from_name' => $fromName
            ]);

            if (!$apiKey || !$apiSecret) {
                \Log::error('❌ [MAILJET] Missing credentials', [
                    'has_api_key' => !empty($apiKey),
                    'has_api_secret' => !empty($apiSecret)
                ]);
                throw new \Exception('Mailjet credentials not configured in .env');
            }

            $ch = curl_init();

            $vars = [
                'Messages' => [
                    [
                        'From' => [
                            'Email' => $fromEmail,
                            'Name' => $fromName ?? env('APP_NAME', 'Amen Our Roots Africa')
                        ],
                        'To' => [
                            [
                                'Email' => $email,
                                'Name' => 'Recipient',
                            ]
                        ],
                        'Subject' => $subject,
                        'TextPart' => strip_tags($htmlContent),
                        'HTMLPart' => $htmlContent,
                        'CustomID' => 'AmenOurRootsEmail',
                    ]
                ]
            ];

            $jsonPayload = json_encode($vars);

            \Log::debug('📤 [MAILJET] Request payload prepared', [
                'recipient' => $email,
                'payload_size' => strlen($jsonPayload),
                'has_html' => !empty($htmlContent)
            ]);

            curl_setopt($ch, CURLOPT_URL, "https://api.mailjet.com/v3.1/send");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $headers = [
                'Content-Type: application/json; charset=utf-8',
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_USERPWD, "{$apiKey}:{$apiSecret}");
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

            \Log::debug('🌐 [MAILJET] Sending request to API endpoint');

            $server_output = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_errno = curl_errno($ch);
            $curl_error = curl_error($ch);

            \Log::info('📩 [MAILJET] API response received', [
                'http_code' => $http_code,
                'curl_errno' => $curl_errno,
                'curl_error' => $curl_error,
                'response_length' => strlen($server_output)
            ]);

            if ($curl_errno) {
                \Log::error('❌ [MAILJET] cURL error', [
                    'error_code' => $curl_errno,
                    'error_message' => $curl_error,
                    'recipient' => $email
                ]);
                curl_close($ch);
                throw new \Exception("cURL Error ({$curl_errno}): {$curl_error}");
            }

            curl_close($ch);

            $response = json_decode($server_output, true);

            \Log::debug('📋 [MAILJET] Response parsed', [
                'response' => $response
            ]);

            if ($http_code === 200 && isset($response['Messages'][0]['Status'])) {
                $status = $response['Messages'][0]['Status'];
                
                if ($status === 'success') {
                    \Log::info('✅ [MAILJET] Email sent successfully', [
                        'recipient' => $email,
                        'status' => $status,
                        'http_code' => $http_code
                    ]);
                    return true;
                } else {
                    $errorMsg = $response['Messages'][0]['Errors'][0]['ErrorMessage'] ?? 'Unknown error';
                    \Log::error('❌ [MAILJET] Email sending failed', [
                        'status' => $status,
                        'error' => $errorMsg,
                        'recipient' => $email,
                        'http_code' => $http_code
                    ]);
                    throw new \Exception("Mailjet Error: {$errorMsg}");
                }
            } else {
                \Log::error('❌ [MAILJET] Invalid response from API', [
                    'http_code' => $http_code,
                    'response' => $response,
                    'recipient' => $email
                ]);
                throw new \Exception("Invalid response from Mailjet (HTTP {$http_code})");
            }

        } catch (\Exception $e) {
            \Log::error('❌ [MAILJET] Exception thrown', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'email' => $email ?? 'unknown'
            ]);
            throw $e;
        }
    }
}
