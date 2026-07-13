<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class Auth0TokenVerifier
{
    private string $domain;
    private string $clientId;

    public function __construct()
    {
        $this->domain = config('services.auth0.domain', 'dev-tc1g3yu0qc1tsnit.us.auth0.com');
        $this->clientId = config('services.auth0.client_id', 'ZMPQwDRJ1rwp60KsPu4IthKdcT8pxXK1');
    }

    /**
     * Decodes and verifies the Auth0 JWT ID Token.
     * Returns the verified claims array, or throws an Exception on failure.
     */
    public function verify(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Invalid JWT format.');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $header = json_decode($this->base64UrlDecode($headerB64), true);
        $payload = json_decode($this->base64UrlDecode($payloadB64), true);

        if (!$header || !$payload) {
            throw new Exception('Invalid JWT encoding.');
        }

        // 1. Verify Issuer (iss)
        $expectedIssuer = "https://{$this->domain}/";
        if (($payload['iss'] ?? '') !== $expectedIssuer) {
            throw new Exception('Invalid token issuer.');
        }

        // 2. Verify Audience (aud)
        $aud = $payload['aud'] ?? '';
        if (is_array($aud)) {
            if (!in_array($this->clientId, $aud)) {
                throw new Exception('Invalid token audience.');
            }
        } elseif ($aud !== $this->clientId) {
            throw new Exception('Invalid token audience.');
        }

        // 3. Verify Expiration (exp)
        if (($payload['exp'] ?? 0) < time()) {
            throw new Exception('Token has expired.');
        }

        // 4. Cryptographic Signature Verification
        $kid = $header['kid'] ?? null;
        if (!$kid) {
            throw new Exception('Missing key ID (kid) in token header.');
        }

        $publicKey = $this->getPublicKeyPem($kid);
        if (!$publicKey) {
            throw new Exception('JWK public key not found for kid.');
        }

        $dataToVerify = "{$headerB64}.{$payloadB64}";
        $signature = $this->base64UrlDecode($signatureB64);

        $verifyResult = openssl_verify($dataToVerify, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($verifyResult !== 1) {
            throw new Exception('JWT signature verification failed.');
        }

        return $payload;
    }

    /**
     * Retrieves the JWKS public key formatted as a PEM string.
     */
    private function getPublicKeyPem(string $kid): ?string
    {
        $jwks = Cache::remember("auth0_jwks", 86400, function () {
            $response = Http::get("https://{$this->domain}/.well-known/jwks.json");
            if ($response->failed()) {
                throw new Exception('Failed to fetch JWKS keys from Auth0.');
            }
            return $response->json();
        });

        $jwk = null;
        foreach ($jwks['keys'] ?? [] as $key) {
            if (($key['kid'] ?? '') === $kid) {
                $jwk = $key;
                break;
            }
        }

        if (!$jwk) {
            // Clear cache and retry once if not found (in case keys were rotated)
            Cache::forget("auth0_jwks");
            $response = Http::get("https://{$this->domain}/.well-known/jwks.json");
            if ($response->successful()) {
                $jwks = $response->json();
                Cache::put("auth0_jwks", $jwks, 86400);
                foreach ($jwks['keys'] ?? [] as $key) {
                    if (($key['kid'] ?? '') === $kid) {
                        $jwk = $key;
                        break;
                    }
                }
            }
        }

        if (!$jwk || empty($jwk['n']) || empty($jwk['e'])) {
            return null;
        }

        return $this->jwkToPem($jwk['n'], $jwk['e']);
    }

    /**
     * Converts JWK modulus (n) and exponent (e) parameters to PEM format.
     */
    private function jwkToPem(string $n, string $e): string
    {
        $modulus = $this->base64UrlDecode($n);
        $exponent = $this->base64UrlDecode($e);

        // RSA Public Key components wrapper (ASN.1 DER structure helper)
        $components = array(
            'modulus' => pack('Ca*a*', 0x02, $this->encodeLength(strlen($modulus)), $modulus),
            'publicExponent' => pack('Ca*a*', 0x02, $this->encodeLength(strlen($exponent)), $exponent)
        );

        $rsapublickey = pack(
            'Ca*a*',
            0x30,
            $this->encodeLength(strlen($components['modulus']) + strlen($components['publicExponent'])),
            $components['modulus'] . $components['publicExponent']
        );

        // SubjectPublicKeyInfo (standard public key structure) wrapper
        $rsaAlgorithmIdentifier = pack('H*', '300d06092a864886f70d0101010500'); // RSA Encryption algorithm OID
        $publicKey = pack(
            'Ca*a*',
            0x03,
            $this->encodeLength(strlen($rsapublickey) + 1),
            "\0" . $rsapublickey
        );

        $der = pack(
            'Ca*a*',
            0x30,
            $this->encodeLength(strlen($rsaAlgorithmIdentifier) + strlen($publicKey)),
            $rsaAlgorithmIdentifier . $publicKey
        );

        $pem = "-----BEGIN PUBLIC KEY-----\n" .
               chunk_split(base64_encode($der), 64, "\n") .
               "-----END PUBLIC KEY-----\n";

        return $pem;
    }

    /**
     * DER length encoder helper
     */
    private function encodeLength(int $length): string
    {
        if ($length <= 0x7F) {
            return chr($length);
        }

        $temp = ltrim(pack('N', $length), chr(0));
        return chr(0x80 | strlen($temp)) . $temp;
    }

    private function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }
}
