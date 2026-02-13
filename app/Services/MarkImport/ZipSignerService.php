<?php

namespace App\Services\MarkImport;

use Illuminate\Support\Facades\Log;

/**
 * ZipSignerService
 *
 * Provides cryptographic signing and verification for ZIP archives.
 * Uses HMAC-SHA256 with Laravel APP_KEY for digital signatures.
 */
class ZipSignerService
{
    /**
     * Sign a manifest and return base64-encoded signature
     *
     * @param array $manifest
     * @return string Base64-encoded signature
     */
    public function signManifest(array $manifest): string
    {
        $manifestJson = json_encode($manifest, JSON_UNESCAPED_SLASHES);
        
        $signature = hash_hmac(
            'sha256',
            $manifestJson,
            config('app.key'),
            false  // raw_output = false, returns hex string
        );

        return base64_encode($signature);
    }

    /**
     * Verify a signed manifest
     *
     * @param array $manifest
     * @param string $signature Base64-encoded signature
     * @return bool
     */
    public function verifyManifest(array $manifest, string $signature): bool
    {
        try {
            $expected = $this->signManifest($manifest);
            
            // Use constant-time comparison to prevent timing attacks
            return hash_equals($expected, $signature);
        } catch (\Exception $e) {
            Log::warning('ZIP signature verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Compute SHA-256 hash of a file
     *
     * @param string $filePath
     * @return string Hex-encoded SHA-256 hash
     */
    public function hashFile(string $filePath): string
    {
        return hash_file('sha256', $filePath);
    }

    /**
     * Compute SHA-256 hash of data
     *
     * @param string $data
     * @return string Hex-encoded SHA-256 hash
     */
    public function hashData(string $data): string
    {
        return hash('sha256', $data);
    }

    /**
     * Generate cryptographically secure random token
     *
     * @param int $length
     * @return string
     */
    public function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Add signatures to manifest
     *
     * Manifest structure:
     * {
     *   "system": "IRMS",
     *   "school_id": 12,
     *   ...
     *   "files": {
     *     "PHY_2026_S0325.csv": "sha256:..."
     *   }
     * }
     *
     * @param array $manifest
     * @return array Manifest with signature
     */
    public function addSignatureToManifest(array $manifest): array
    {
        $signature = $this->signManifest($manifest);

        $manifest['signature'] = [
            'algorithm' => 'HMAC-SHA256',
            'value' => $signature,
            'signed_at' => now()->toIso8601String(),
            'signed_by' => auth()->id() ?? 'system',
        ];

        return $manifest;
    }

    /**
     * Verify manifest signature
     *
     * @param array $manifest
     * @return bool
     */
    public function verifyManifestSignature(array $manifest): bool
    {
        if (!isset($manifest['signature']['value'])) {
            return false;
        }

        $signature = $manifest['signature']['value'];
        
        // Remove signature from manifest before verification
        unset($manifest['signature']);

        return $this->verifyManifest($manifest, $signature);
    }

    /**
     * Log signature event for audit trail
     *
     * @param string $action (sign | verify)
     * @param string $zipHash
     * @param bool $result
     * @return void
     */
    public function logSignatureEvent(
        string $action,
        string $zipHash,
        bool $result
    ): void {
        Log::channel('audit')->info('ZIP Signature Event', [
            'action' => $action,
            'zip_hash' => $zipHash,
            'result' => $result ? 'success' : 'failed',
            'user_id' => auth()->id() ?? 'system',
            'timestamp' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
        ]);
    }
}
